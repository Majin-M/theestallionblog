<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
// "order" est un mot réservé SQL, on nomme la table "orders"
#[ORM\Table(name: '`orders`')]
class Order
{
    // ── Statuts possibles d'une commande ──
    // pending  : commande créée, paiement non encore effectué
    // paid     : paiement Stripe confirmé
    // cancelled: paiement annulé ou échoué
    const STATUS_PENDING   = 'pending';
    const STATUS_PAID      = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // L'utilisateur qui a passé la commande
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    // Date et heure de création de la commande
    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    // Statut courant de la commande (pending, paid, cancelled)
    #[ORM\Column(length: 20)]
    private string $status = self::STATUS_PENDING;

    // Total TTC de la commande en euros (calculé à la création)
    #[ORM\Column]
    private ?float $total = null;

    // Identifiant de session Stripe — permet de retrouver la session
    // de paiement côté Stripe si besoin (remboursement, vérification, etc.)
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripeSessionId = null;

    // Lignes de commande (un enregistrement par projet commandé)
    #[ORM\OneToMany(targetEntity: OrderItem::class, mappedBy: 'order', cascade: ['persist', 'remove'])]
    private Collection $orderItems;
    #[ORM\Column(length: 100)]
    private ?string $shippingFirstname = null;

    #[ORM\Column(length: 100)]
    private ?string $shippingLastname = null;

    #[ORM\Column(length: 255)]
    private ?string $shippingAddress = null;

    #[ORM\Column(length: 10)]
    private ?string $shippingPostalCode = null;

    #[ORM\Column(length: 100)]
    private ?string $shippingCity = null;

    #[ORM\Column(length: 100)]
    private ?string $shippingCountry = null;

    // + les getters/setters correspondants

    public function __construct()
    {
        $this->orderItems  = new ArrayCollection();
        $this->createdAt   = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }
    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }
    public function setTotal(float $total): static
    {
        $this->total = $total;
        return $this;
    }

    public function getStripeSessionId(): ?string
    {
        return $this->stripeSessionId;
    }
    public function setStripeSessionId(?string $id): static
    {
        $this->stripeSessionId = $id;
        return $this;
    }

    public function getOrderItems(): Collection
    {
        return $this->orderItems;
    }

    public function getShippingFirstname(): ?string
    {
        return $this->shippingFirstname;
    }

    public function setShippingFirstname(?string $shippingFirstname): self
    {
        $this->shippingFirstname = $shippingFirstname;
        return $this;
    }

    public function getShippingLastname(): ?string
    {
        return $this->shippingLastname;
    }

    public function setShippingLastname(?string $shippingLastname): self
    {
        $this->shippingLastname = $shippingLastname;
        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(?string $shippingAddress): self
    {
        $this->shippingAddress = $shippingAddress;
        return $this;
    }

    public function getShippingPostalCode(): ?string
    {
        return $this->shippingPostalCode;
    }

    public function setShippingPostalCode(?string $shippingPostalCode): self
    {
        $this->shippingPostalCode = $shippingPostalCode;
        return $this;
    }

    public function getShippingCity(): ?string
    {
        return $this->shippingCity;
    }

    public function setShippingCity(?string $shippingCity): self
    {
        $this->shippingCity = $shippingCity;
        return $this;
    }

    public function getShippingCountry(): ?string
    {
        return $this->shippingCountry;
    }

    public function setShippingCountry(?string $shippingCountry): self
    {
        $this->shippingCountry = $shippingCountry;
        return $this;
    }

    public function addOrderItem(OrderItem $item): static
    {
        if (!$this->orderItems->contains($item)) {
            $this->orderItems->add($item);
            $item->setOrder($this);
        }
        return $this;
    }

    public function removeOrderItem(OrderItem $item): static
    {
        if ($this->orderItems->removeElement($item)) {
            if ($item->getOrder() === $this) {
                $item->setOrder(null);
            }
        }
        return $this;
    }
}
