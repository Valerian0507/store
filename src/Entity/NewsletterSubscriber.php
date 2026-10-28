<?php

namespace App\Entity;

use App\Repository\NewsletterSubscriberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NewsletterSubscriberRepository::class)]
#[ORM\Table(name: 'newsletter_subscribers')]
class NewsletterSubscriber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    // Jeton aléatoire du lien de désinscription (pas d'identifiant devinable dans l'URL)
    #[ORM\Column(length: 64, unique: true)]
    private string $unsubscribeToken;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(string $email)
    {
        $this->email = mb_strtolower(trim($email));
        $this->unsubscribeToken = bin2hex(random_bytes(32));
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): string { return $this->email; }

    public function getUnsubscribeToken(): string { return $this->unsubscribeToken; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
}
