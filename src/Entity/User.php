<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Entity\Address;
use App\Entity\Order;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $firstName = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(type: 'json')]
    private array $roles = [];

    #[ORM\Column]
    private string $password;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Address::class, orphanRemoval: true)]
    private Collection $addresses;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Order::class)]
    private Collection $orders;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->addresses = new ArrayCollection();
        $this->orders = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

        public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * Summary of getFullName
     * @return string
     */
    public function getFullName(): string
    {
        return trim(($this->firstName ?? '').' '.($this->lastName ?? ''));
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    public function addAddress(Address $address): static
    {
        if(!$this->addresses->contains($address)) {
            $this->addresses->add($address);
            $address->setUser($this);
        }
        return $this;
    }

    public function removeAddress(Address $address): static
    {
        if ($this->addresses->removeElement($address)) {
            if ($address->getUser() === $this) {
                $address->setUser(null);
            }
        }

        return $this;
    }

    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function getOrders(): Collection
    {
        return $this->orders;
    }

    public function addOrder(Order $order): static
    {
        if(!$this->orders->contains($order)) {
            $this->orders->add($order);
            $order->setUser($this);
        }
        return $this;
    }

    // Что бы отвязать от пользователя заказ (Рассмотреть эту часть)
    public function removeOrder(Order $order): static
    {
        $this->orders->removeElement($order);
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

}
