<?php

namespace App\Controller;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Form\AddressType;
use App\Repository\ProjectRepository;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Stripe;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/commande', name: 'app_order_')]
class OrderController extends AbstractController
{
    public function __construct(private RequestStack $requestStack) {}

    // ── Helper : lit le panier depuis la session ──
    private function getCart(): array
    {
        return $this->requestStack->getSession()->get('cart', []);
    }

    private function clearCart(): void
    {
        $this->requestStack->getSession()->set('cart', []);
    }

    // ─────────────────────────────────────────────────────────────
    // CHECKOUT — Récapitulatif avant paiement
    // L'utilisateur doit être connecté (#[IsGranted])
    // ─────────────────────────────────────────────────────────────
    #[Route('/recapitulatif', name: 'checkout')]
    #[IsGranted('ROLE_USER')]
    public function checkout(
        ProjectRepository      $projectRepository,
        EntityManagerInterface $em,
        Request                $request
    ): Response {
        $cart = $this->getCart();

        if (empty($cart)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('app_projet');
        }

        // Construit les articles (inchangé)
        $cartItems = [];
        $total = $totalHT = 0.0;
        foreach ($cart as $projectId => $quantity) {
            $project = $projectRepository->find($projectId);
            if (!$project) continue;
            $lineTotal    = $project->getPrice() * $quantity;
            $lineTotalTTC = $lineTotal * (1 + $project->getTva() / 100);
            $cartItems[]  = compact('project', 'quantity', 'lineTotal', 'lineTotalTTC');
            $totalHT     += $lineTotal;
            $total       += $lineTotalTTC;
        }

        // Formulaire d'adresse lié à un Order temporaire (pas encore persisté)
        $order = new Order();
        $form  = $this->createForm(AddressType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // On stocke l'adresse en session pour la récupérer dans pay()
            $this->requestStack->getSession()->set('shipping', [
                'firstname'  => $order->getShippingFirstname(),
                'lastname'   => $order->getShippingLastname(),
                'address'    => $order->getShippingAddress(),
                'postalCode' => $order->getShippingPostalCode(),
                'city'       => $order->getShippingCity(),
                'country'    => $order->getShippingCountry(),
            ]);

            // Formulaire valide → on part au paiement Stripe
            return $this->redirectToRoute('app_order_pay');
        }

        return $this->render('order/checkout.html.twig', [
            'cartItems' => $cartItems,
            'totalHT'   => $totalHT,
            'totalTVA'  => $total - $totalHT,
            'total'     => $total,
            'form'      => $form,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // CREATE STRIPE SESSION — Crée la session de paiement Stripe
    // et redirige l'utilisateur vers la page de paiement hébergée.
    //
    // Stripe Checkout héberge la page de paiement côté Stripe :
    // - Sécurisé (carte saisie directement sur les serveurs Stripe)
    // - Zéro intégration frontend complexe
    // - Conforme PCI DSS automatiquement
    // ─────────────────────────────────────────────────────────────
    #[Route('/payer', name: 'pay')]
    #[IsGranted('ROLE_USER')]
    public function  pay(
        ProjectRepository      $projectRepository,
        EntityManagerInterface $em,
    ): Response {
        $cart = $this->getCart();

        if (empty($cart)) {
            return $this->redirectToRoute('app_projet');
        }

        // ── 1. Initialise le SDK Stripe avec la clé secrète ──
        // La clé est stockée dans .env.local (jamais dans le code)
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        // ── 2. Construit les "line_items" pour Stripe ──
        // Stripe a besoin du prix en centimes (int), donc on multiplie par 100
        $lineItems = [];
        $cartItems = [];
        $total     = 0.0;

        foreach ($cart as $projectId => $quantity) {
            $project = $projectRepository->find($projectId);
            if (!$project) continue;

            // Prix TTC unitaire en centimes (Stripe travaille en centimes)
            $unitPriceTTCCents = (int) round(
                $project->getPrice() * (1 + $project->getTva() / 100) * 100
            );

            $lineItems[] = [
                'price_data' => [
                    'currency'     => 'eur',
                    'product_data' => [
                        'name' => $project->getName(),
                        // On peut ajouter une description et une image ici
                        // 'description' => $project->getGenre(),
                        // 'images'      => [$baseUrl . '/images/' . $project->getImage()],
                    ],
                    // Prix en centimes TTC
                    'unit_amount'  => $unitPriceTTCCents,
                ],
                'quantity' => $quantity,
            ];

            $lineTotal = $project->getPrice() * $quantity;
            $total    += $lineTotal * (1 + $project->getTva() / 100);

            $cartItems[] = [
                'project'  => $project,
                'quantity' => $quantity,
                'total'    => $lineTotal,
            ];
        }

        // ── 3. Crée la commande en base avec statut "pending" ──
        // On la sauvegarde AVANT de rediriger vers Stripe.
        // Si l'utilisateur ferme le navigateur après le paiement,
        // on peut retrouver la commande via le stripeSessionId dans le webhook.
        $order = new Order();
        // Récupère l'adresse stockée en session lors du checkout
        $shipping = $this->requestStack->getSession()->get('shipping', []);

        $order->setShippingFirstname($shipping['firstname'] ?? '');
        $order->setShippingLastname($shipping['lastname']   ?? '');
        $order->setShippingAddress($shipping['address']     ?? '');
        $order->setShippingPostalCode($shipping['postalCode'] ?? '');
        $order->setShippingCity($shipping['city']           ?? '');
        $order->setShippingCountry($shipping['country']     ?? '');
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $order->setUser($this->getUser());
        $order->setTotal($total);
        $order->setStatus(Order::STATUS_PENDING);

        foreach ($cartItems as $item) {
            $orderItem = new OrderItem();
            $orderItem->setProject($item['project']);
            $orderItem->setQuantity($item['quantity']);
            $orderItem->setUnitPrice($item['project']->getPrice()); // snapshot HT
            $orderItem->setTva($item['project']->getTva());          // snapshot TVA
            $order->addOrderItem($orderItem);
        }

        $em->persist($order);
        $em->flush();

        // ── 4. Crée la session Stripe Checkout ──
        $stripeSession = StripeSession::create([
            'payment_method_types' => ['card'],
            'line_items'           => $lineItems,
            'mode'                 => 'payment',

            // URL de redirection après paiement réussi
            // On passe l'id de la commande pour la retrouver
            'success_url' => $this->generateUrl(
                'app_order_success',
                ['orderId' => $order->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),

            // URL de redirection si l'utilisateur annule sur Stripe
            'cancel_url'  => $this->generateUrl(
                'app_order_cancel',
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),

            // Metadata : informations supplémentaires stockées côté Stripe
            'metadata' => [

                'order_id' => $order->getId(),
                'user_id'  => $user->getId(),
            ],
        ]);

        // ── 5. Sauvegarde l'id de session Stripe dans la commande ──
        $order->setStripeSessionId($stripeSession->id);
        $em->flush();

        // ── 6. Redirige vers la page Stripe hébergée ──
        return $this->redirect($stripeSession->url);
    }

    // ─────────────────────────────────────────────────────────────
    // SUCCESS — Stripe redirige ici après un paiement réussi
    // ─────────────────────────────────────────────────────────────
    #[Route('/succes/{orderId}', name: 'success')]
    #[IsGranted('ROLE_USER')]
    public function success(
        int                    $orderId,
        OrderRepository        $orderRepository,
        EntityManagerInterface $em
    ): Response {
        $order = $orderRepository->find($orderId);

        // Sécurité : la commande doit appartenir à l'utilisateur connecté
        if (!$order || $order->getUser() !== $this->getUser()) {
            return $this->redirectToRoute('app_projet');
        }

        // On passe la commande en statut "paid" et on vide le panier
        if ($order->getStatus() === Order::STATUS_PENDING) {
            $order->setStatus(Order::STATUS_PAID);
            $em->flush();
            $this->clearCart();
        }

        return $this->render('order/success.html.twig', [
            'order' => $order,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // CANCEL — Stripe redirige ici si l'utilisateur annule
    // ─────────────────────────────────────────────────────────────
    #[Route('/annulation', name: 'cancel')]
    public function cancel(): Response
    {
        $this->addFlash('warning', 'Le paiement a été annulé. Votre panier est conservé.');
        return $this->render('order/cancel.html.twig');
    }
}
