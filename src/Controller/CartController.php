<?php

namespace App\Controller;

use App\Repository\ProjectRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier', name: 'app_cart_')]
class CartController extends AbstractController
{
    /**
     * On injecte RequestStack plutôt que Session directement.
     * C'est la méthode recommandée depuis Symfony 5.3.
     * $requestStack->getSession() retourne la session courante.
     */
    public function __construct(private RequestStack $requestStack) {}

    // ─────────────────────────────────────────────────────────────
    // HELPER PRIVÉ — récupère le tableau du panier depuis la session
    // Structure stockée : [ project_id => quantity, ... ]
    //   ex: [ 3 => 2, 7 => 1 ]
    // ─────────────────────────────────────────────────────────────
    private function getCart(): array
    {
        // get('cart', []) retourne [] si la clé 'cart' n'existe pas encore
        return $this->requestStack->getSession()->get('cart', []);
    }

    private function saveCart(array $cart): void
    {
        $this->requestStack->getSession()->set('cart', $cart);
    }

    // ─────────────────────────────────────────────────────────────
    // INDEX — Affiche le contenu du panier
    // ─────────────────────────────────────────────────────────────
    #[Route('', name: 'index')]
    public function index(ProjectRepository $projectRepository): Response
    {
        // On récupère le panier brut depuis la session
        $cart = $this->getCart();

        // On construit un tableau enrichi avec les entités Project
        // pour pouvoir afficher nom, image, prix, etc. dans le template
        $cartItems = [];
        $total     = 0.0;

        foreach ($cart as $projectId => $quantity) {
            // On cherche le projet en base via son id
            $project = $projectRepository->find($projectId);

            // Sécurité : si le projet a été supprimé entre-temps, on le retire du panier
            if (!$project) {
                unset($cart[$projectId]);
                continue;
            }

            // Prix HT de la ligne
            $lineTotal = $project->getPrice() * $quantity;

            // Prix TTC de la ligne (on applique la TVA stockée sur le projet)
            $lineTotalTTC = $lineTotal * (1 + $project->getTva() / 100);

            $cartItems[] = [
                'project'      => $project,
                'quantity'     => $quantity,
                'lineTotal'    => $lineTotal,
                'lineTotalTTC' => $lineTotalTTC,
            ];

            $total += $lineTotalTTC;
        }

        // On sauvegarde le panier nettoyé (projets supprimés retirés)
        $this->saveCart($cart);

        return $this->render('cart/index.html.twig', [
            'cartItems' => $cartItems,
            'total'     => $total,
        ]);
    }

    // ─────────────────────────────────────────────────────────────
    // ADD — Ajoute un projet au panier (ou incrémente sa quantité)
    // Appelé depuis le bouton "Ajouter" sur la page projets
    // ─────────────────────────────────────────────────────────────
    #[Route('/ajouter/{id}', name: 'add', requirements: ['id' => '\d+'])]
    public function add(int $id, ProjectRepository $projectRepository): Response
    {
        // On vérifie que le projet existe bien en base avant d'ajouter
        $project = $projectRepository->find($id);

        if (!$project) {
            // Projet inexistant : on redirige avec un message flash d'erreur
            $this->addFlash('danger', 'Projet introuvable.');
            return $this->redirectToRoute('app_projet');
        }

        $cart = $this->getCart();

        // Si le projet est déjà dans le panier, on incrémente la quantité
        // Sinon on l'initialise à 1
        $cart[$id] = isset($cart[$id]) ? $cart[$id] + 1 : 1;

        $this->saveCart($cart);

        $this->addFlash('success', '"' . $project->getName() . '" ajouté au panier.');

        // On redirige vers la page d'où l'on vient (les projets)
        return $this->redirectToRoute('app_projects');
    }

    // ─────────────────────────────────────────────────────────────
    // DECREASE — Décrémente la quantité d'un projet (min 1)
    // ─────────────────────────────────────────────────────────────
    #[Route('/diminuer/{id}', name: 'decrease', requirements: ['id' => '\d+'])]
    public function decrease(int $id): Response
    {
        $cart = $this->getCart();

        if (isset($cart[$id])) {
            if ($cart[$id] > 1) {
                // On décrémente
                $cart[$id]--;
            } else {
                // Quantité = 1 → on supprime l'entrée du panier
                unset($cart[$id]);
            }
        }

        $this->saveCart($cart);

        return $this->redirectToRoute('app_cart_index');
    }

    // ─────────────────────────────────────────────────────────────
    // REMOVE — Supprime complètement un projet du panier
    // ─────────────────────────────────────────────────────────────
    #[Route('/supprimer/{id}', name: 'remove', requirements: ['id' => '\d+'])]
    public function remove(int $id): Response
    {
        $cart = $this->getCart();

        // unset() ne plante pas si la clé n'existe pas
        unset($cart[$id]);

        $this->saveCart($cart);

        $this->addFlash('info', 'Projet retiré du panier.');

        return $this->redirectToRoute('app_cart_index');
    }

    // ─────────────────────────────────────────────────────────────
    // CLEAR — Vide complètement le panier
    // ─────────────────────────────────────────────────────────────
    #[Route('/vider', name: 'clear')]
    public function clear(): Response
    {
        // On remplace le panier par un tableau vide en session
        $this->saveCart([]);

        $this->addFlash('info', 'Votre panier a été vidé.');

        return $this->redirectToRoute('app_cart_index');
    }
}