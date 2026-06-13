<?php

namespace App\Entity;

use App\Repository\AddressRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressRepository::class)]
#[ORM\Table(name: 'addresses')]
class Address
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private ?User $user = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le pays est obligatoire.')]
    #[Assert\Length(max: 50, maxMessage: 'Le pays ne peut pas dépasser {{ limit }} caractères.')]
    private string $country;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'La ville est obligatoire.')]
    #[Assert\Length(max: 120, maxMessage: 'La ville ne peut pas dépasser {{ limit }} caractères.')]
    private string $city;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'L\'adresse est obligatoire.')]
    #[Assert\Length(max: 120, maxMessage: 'L\'adresse ne peut pas dépasser {{ limit }} caractères.')]
    private string $street;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le code postal est obligatoire.')]
    #[Assert\Length(max: 50, maxMessage: 'Le code postal ne peut pas dépasser {{ limit }} caractères.')]
    private string $postalCode;

    #[ORM\Column(options: ['default' => false])]
    private bool $isDefault = false;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
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

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }

    public function getCity(): string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getStreet(): string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setIsDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

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


    /**
     * Get the value of label
     */
    // public function getLabel()
    // {
    //     return $this->label;
    // }

    // /**
    //  * Set the value of label
    //  *
    //  * @return  self
    //  */
    // public function setLabel($label)
    // {
    //     $this->label = $label;

    //     return $this;
    // }
}
