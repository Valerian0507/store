<?php

namespace App\Entity;

use App\Repository\CarePlanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CarePlanRepository::class)]
#[ORM\Table(name: 'care_plans')]
#[ORM\UniqueConstraint(name: 'UNIQ_CARE_PLAN_USER_PRODUCT', columns: ['user_id', 'product_id'])]
#[ORM\Index(name: 'IDX_CARE_PLAN_USER', columns: ['user_id'])]
#[ORM\Index(name: 'IDX_CARE_PLAN_PRODUCT', columns: ['product_id'])]
class CarePlan
{
    public const INTERVALS = [4, 6, 8, 12];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column]
    private int $intervalWeeks = 6;

    #[ORM\Column(length: 16)]
    private string $status = 'active';

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $nextDate = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(User $user, Product $product, int $quantity, int $intervalWeeks, ?\DateTimeImmutable $now = null)
    {
        $this->user = $user;
        $this->product = $product;
        $this->createdAt = $now ?? new \DateTimeImmutable();
        $this->configure($quantity, $intervalWeeks, $this->createdAt);
    }

    public function getId(): ?int { return $this->id; }
    public function getUser(): User { return $this->user; }
    public function getProduct(): Product { return $this->product; }
    public function getQuantity(): int { return $this->quantity; }
    public function getIntervalWeeks(): int { return $this->intervalWeeks; }
    public function getStatus(): string { return $this->status; }
    public function getNextDate(): ?\DateTimeImmutable { return $this->nextDate; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function configure(int $quantity, int $intervalWeeks, ?\DateTimeImmutable $now = null): void
    {
        if ($quantity < 1 || $quantity > 20 || !in_array($intervalWeeks, self::INTERVALS, true)) {
            throw new \InvalidArgumentException('Choisissez 1 à 20 unités et un intervalle de 4, 6, 8 ou 12 semaines.');
        }
        if ($this->status === 'cancelled') {
            throw new \DomainException('Ce planning est annulé. Créez-en un nouveau depuis le produit.');
        }
        $this->quantity = $quantity;
        $this->intervalWeeks = $intervalWeeks;
        if ($this->status === 'active') $this->schedule($now);
    }

    public function pause(): void
    {
        if ($this->status !== 'active') throw new \DomainException('Seul un planning actif peut être mis en pause.');
        $this->status = 'paused';
        $this->nextDate = null;
    }

    public function resume(?\DateTimeImmutable $now = null): void
    {
        if ($this->status !== 'paused') throw new \DomainException('Seul un planning en pause peut être repris.');
        $this->status = 'active';
        $this->schedule($now);
    }

    public function cancel(): void
    {
        $this->status = 'cancelled';
        $this->nextDate = null;
    }

    public function restart(int $quantity, int $intervalWeeks, ?\DateTimeImmutable $now = null): void
    {
        if ($quantity < 1 || $quantity > 20 || !in_array($intervalWeeks, self::INTERVALS, true)) {
            throw new \InvalidArgumentException('Paramètres de planning invalides.');
        }
        $this->status = 'active';
        $this->configure($quantity, $intervalWeeks, $now);
    }

    private function schedule(?\DateTimeImmutable $now): void
    {
        $this->nextDate = ($now ?? new \DateTimeImmutable())->setTime(0, 0)->modify('+' . $this->intervalWeeks . ' weeks');
    }
}
