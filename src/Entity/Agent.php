<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\AgentRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: AgentRepository::class)]
#[ORM\Table(name: 'agents')]
class Agent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 64, unique: true)]
    private string $symbol;

    #[ORM\Column(length: 64)]
    private string $faction;

    #[ORM\Column(type: 'text')]
    private string $encryptedToken;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Gedmo\Blameable(on: 'create')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    #[Gedmo\Blameable(on: 'update')]
    private ?User $updatedBy = null;

    public function __construct(User $user, string $symbol, string $faction, string $encryptedToken)
    {
        $this->user = $user;
        $this->symbol = $symbol;
        $this->faction = $faction;
        $this->encryptedToken = $encryptedToken;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function getFaction(): string
    {
        return $this->faction;
    }

    public function getEncryptedToken(): string
    {
        return $this->encryptedToken;
    }

    public function setEncryptedToken(string $encryptedToken): void
    {
        $this->encryptedToken = $encryptedToken;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function getCreatedBy(): ?User
    {
        return $this->createdBy;
    }

    public function getUpdatedBy(): ?User
    {
        return $this->updatedBy;
    }
}
