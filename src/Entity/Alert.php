<?php

namespace App\Entity;

use App\Repository\AlertRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AlertRepository::class)]
#[ORM\Table(name: 'alerts')]
class Alert
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_SENT    = 'sent';
    public const STATUS_FAILED  = 'failed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'alerts')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: "L'utilisateur est obligatoire.")]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: FireDetection::class, inversedBy: 'alerts')]
    #[ORM\JoinColumn(name: 'detection_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?FireDetection $detection = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Le message est obligatoire.')]
    #[Assert\Length(min: 10, max: 2000)]
    private string $message;

    #[ORM\Column(length: 20, options: ['default' => 'pending'])]
    private string $status = self::STATUS_PENDING;

    #[ORM\Column(name: 'sent_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    // ─── Getters / Setters ───────────────────────────────────

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

    public function getDetection(): ?FireDetection
    {
        return $this->detection;
    }

    public function setDetection(?FireDetection $detection): static
    {
        $this->detection = $detection;
        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): static
    {
        $this->message = $message;
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

    public function getSentAt(): ?\DateTimeImmutable
    {
        return $this->sentAt;
    }

    public function setSentAt(?\DateTimeImmutable $sentAt): static
    {
        $this->sentAt = $sentAt;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    // ─── Helpers ─────────────────────────────────────────────

    public function isSent(): bool
    {
        return $this->status === self::STATUS_SENT;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Classe Bootstrap pour le badge de statut.
     */
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_SENT    => 'success',
            self::STATUS_FAILED  => 'danger',
            default              => 'warning',
        };
    }

    public function getStatusLabel(): string
    {
        return match($this->status) {
            self::STATUS_SENT    => 'Envoyé',
            self::STATUS_FAILED  => 'Échoué',
            default              => 'En attente',
        };
    }
}
