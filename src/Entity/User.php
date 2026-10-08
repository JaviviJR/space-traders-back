<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private string $email;

    #[ORM\Column]
    private string $password;

    /** @var list<string> */
    #[ORM\Column]
    private array $roles;

    #[ORM\Column(nullable: true)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private bool $isVerified;

    public function __construct(string $email, string $password)
    {
        if ('' === $email) {
            throw new \InvalidArgumentException('User email must not be empty.');
        }

        $this->email = $email;
        $this->password = $password;
        $this->roles = ['ROLE_USER'];
        $this->isVerified = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /** @return non-empty-string */
    public function getEmail(): string
    {
        $email = $this->email;
        assert('' !== $email);

        return $email;
    }

    /** @return non-empty-string */
    public function getUserIdentifier(): string
    {
        return $this->getEmail();
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    /** @return list<string> */
    public function getRoles(): array
    {
        return array_values(array_unique([...$this->roles, 'ROLE_USER']));
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function verify(): void
    {
        $this->isVerified = true;
    }

    public function eraseCredentials(): void
    {
        // No transient sensitive data to clear.
    }
}
