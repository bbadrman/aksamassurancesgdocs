<?php

namespace App\Entity;

use App\Repository\ActivityLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ActivityLogRepository::class)]
#[ORM\Index(columns: ['created_at'], name: 'idx_activity_created_at')]
#[ORM\Index(columns: ['action'], name: 'idx_activity_action')]
class ActivityLog
{
    // Action constants
    public const ACTION_UPLOAD = 'upload';
    public const ACTION_DELETE = 'delete';
    public const ACTION_DOWNLOAD = 'download';
    public const ACTION_EDIT = 'edit';
    public const ACTION_RESTORE = 'restore';
    public const ACTION_PERMANENT_DELETE = 'permanent_delete';
    public const ACTION_CONTRAT_CREATE = 'contrat_create';
    public const ACTION_CONTRAT_EDIT = 'contrat_edit';
    public const ACTION_CONTRAT_DELETE = 'contrat_delete';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    private ?string $action = null;

    #[ORM\ManyToOne(inversedBy: 'activityLogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Document $document = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Contrat $contrat = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $details = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
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

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): static
    {
        $this->document = $document;
        return $this;
    }

    public function getContrat(): ?Contrat
    {
        return $this->contrat;
    }

    public function setContrat(?Contrat $contrat): static
    {
        $this->contrat = $contrat;
        return $this;
    }

    public function getDetails(): ?string
    {
        return $this->details;
    }

    public function setDetails(?string $details): static
    {
        $this->details = $details;
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

    /**
     * Get human-readable action label
     */
    public function getActionLabel(): string
    {
        return match($this->action) {
            self::ACTION_UPLOAD => 'Uploaded document',
            self::ACTION_DELETE => 'Moved to trash',
            self::ACTION_DOWNLOAD => 'Downloaded document',
            self::ACTION_EDIT => 'Edited document',
            self::ACTION_RESTORE => 'Restored from trash',
            self::ACTION_PERMANENT_DELETE => 'Permanently deleted',
            self::ACTION_CONTRAT_CREATE => 'Created contrat',
            self::ACTION_CONTRAT_EDIT => 'Edited contrat',
            self::ACTION_CONTRAT_DELETE => 'Deleted contrat',
            default => $this->action,
        };
    }
}
