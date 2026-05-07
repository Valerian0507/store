<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrderItemRepository::class)]
#[ORM\Table(name: 'order_items')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'order_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')] // Вопросы на счет CASCADE уточнить нужно ли для этого проекта Можно использовать RESTRICT
    private ?Order $order = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'product_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Product $product = null;

    // SNAPSHOT
    #[ORM\Column(length: 255)]
    private string $productName = 'Product';

    #[ORM\Column]
    private int $unitPriceCents = 0;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $productImage = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?Order
    {
        return $this->order;
    }


    public function setOrder(?Order $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;
        return $this;
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function setProductName(string $name): static
    {
        $this->productName = $name;
        return $this;
    }

    public function getUnitPriceCents(): int
    {
        return $this->unitPriceCents;
    }

    public function setUnitPriceCents(int $cents): static
    {
        $this->unitPriceCents = $cents;

        return $this;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    public function setQuantity(int $qty): static
    {
        $this->quantity = max(1, $qty);

        return $this;
    }

    public function getLineTotalCents(): int
    {
        return $this->unitPriceCents * $this->quantity;
    }

    /**
     * Summary of fromProduct
     * @param Product $product
     * @param int $qty
     * @return OrderItem
     */
    public static function fromProduct(Product $product, int $qty): self
    {
        $item = new self();
        $item->setProduct($product);
        // У тебя title nullable, поэтому подстрахуемся
        $item->setProductName($product->getTitle() ?? 'Product');
        // getPriceCents() у тебя возвращает ?int → делаем безопасно
        $item->setUnitPriceCents($product->getPriceCents() ?? 0);
        $item->setQuantity($qty);
        // Для сохранения картинки если даже продукт удалили
        $item->setProductImage($product->getImage());

        return $item;
    }

    public function getProductImage(): ?string
    {
        return $this->productImage;
    }

    public function setProductImage(?string $productImage): static
    {
        $this->productImage = $productImage;

        return $this;
    }
}
