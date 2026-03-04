<?php

namespace App\Entity;

use App\Repository\CartItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CartItemRepository::class)]
#[ORM\Table(name: "cart_item")]
#[ORM\UniqueConstraint(name: "uniq_cart_article", columns: ["cart_id", "article_unique_id"])]
#[ORM\Index(columns: ["cart_id"], name: "idx_cartitem_cart")]
class CartItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: "integer")]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Cart::class, inversedBy: "items")]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    private ?Cart $cart = null;

    // ✅ liaison vers Article via sa PK UniqueId (string)
    #[ORM\ManyToOne(targetEntity: Article::class)]
    #[ORM\JoinColumn(name: "article_unique_id", referencedColumnName: "UniqueId", nullable: false, onDelete: "CASCADE")]
    private ?Article $article = null;

    #[ORM\Column(type: "integer")]
    private int $quantity = 1;

    // ✅ on stocke le prix au moment de l’ajout (vrai panier)
    #[ORM\Column(type: "decimal", precision: 10, scale: 2)]
    private string $unitPriceTtc = "0.00";

    public function getId(): ?int { return $this->id; }

    public function getCart(): ?Cart { return $this->cart; }
    public function setCart(?Cart $cart): self { $this->cart = $cart; return $this; }

    public function getArticle(): ?Article { return $this->article; }
    public function setArticle(?Article $article): self { $this->article = $article; return $this; }

    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): self { $this->quantity = max(0, $quantity); return $this; }

    public function getUnitPriceTtc(): string { return $this->unitPriceTtc; }
    public function setUnitPriceTtc(string $price): self { $this->unitPriceTtc = $price; return $this; }

    public function getLineTotal(): string
    {
        // decimal string -> simple calc (ok pour affichage)
        $q = $this->quantity;
        $p = (float)$this->unitPriceTtc;
        return number_format($q * $p, 2, '.', '');
    }
}