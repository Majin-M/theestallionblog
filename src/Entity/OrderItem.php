<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // La commande parente
    #[ORM\ManyToOne(inversedBy: 'orderItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Order $order = null;

    // Le projet commandé — on garde la référence mais aussi une copie
    // du prix et de la TVA au moment de la commande.
    // Si le prix change plus tard en base, l'historique de commande reste correct.
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Project $project = null;

    // Quantité commandée
    #[ORM\Column]
    private ?int $quantity = null;

    // Prix unitaire HT au moment de la commande (snapshot)
    #[ORM\Column]
    private ?float $unitPrice = null;

    // Taux de TVA au moment de la commande (snapshot)
    #[ORM\Column]
    private ?float $tva = null;

    public function getId(): ?int { return $this->id; }

    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): static { $this->order = $order; return $this; }

    public function getProject(): ?Project { return $this->project; }
    public function setProject(?Project $project): static { $this->project = $project; return $this; }

    public function getQuantity(): ?int { return $this->quantity; }
    public function setQuantity(int $quantity): static { $this->quantity = $quantity; return $this; }

    public function getUnitPrice(): ?float { return $this->unitPrice; }
    public function setUnitPrice(float $unitPrice): static { $this->unitPrice = $unitPrice; return $this; }

    public function getTva(): ?float { return $this->tva; }
    public function setTva(float $tva): static { $this->tva = $tva; return $this; }

    // ── Helpers de calcul ──

    // Total HT de la ligne
    public function getLineTotal(): float
    {
        return $this->unitPrice * $this->quantity;
    }

    // Total TTC de la ligne
    public function getLineTotalTTC(): float
    {
        return $this->getLineTotal() * (1 + $this->tva / 100);
    }
}