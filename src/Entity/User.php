<?php

namespace App\Entity;

use App\Repository\UserRepository;
use App\Enum\Permission;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'This username is already used')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 180, unique: true, nullable: true)]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var list<string> The user permissions
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $permissions = [];

    /**
     * @var string The hashed password
     */
   #[ORM\Column(nullable: true)]
    private ?string $password = null;

     #[ORM\Column(nullable: true, unique: true)]
    private ?int $externalId = null;

     /**
      * @var Collection<int, Contrat>
      */
     #[ORM\OneToMany(targetEntity: Contrat::class, mappedBy: 'user')]
     private Collection $contrats;

     public function __construct()
     {
         $this->contrats = new ArrayCollection();
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

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): static
    {
        $this->username = $username;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_values(array_unique($roles));
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = array_values(array_unique($roles));
        return $this;
    }

    public function hasRole(string $role): bool
    {
        return in_array($role, $this->getRoles(), true);
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        // Admin has all permissions - NEVER check explicit permissions for admin
        if ($this->isAdmin()) {
            return true;
        }
        
        // Get stored permissions
        $permissions = $this->permissions ?? [];
        
        // If no explicit permissions are set, use default permissions
        if (empty($permissions)) {
            return in_array($permission, Permission::getDefaultsForRole('ROLE_USER'), true);
        }
        
        return in_array($permission, $permissions, true);
    }

    /**
     * Check if user is admin (always bypasses all permission checks)
     */
    public function isAdmin(): bool
    {
        return in_array('ROLE_ADMIN', $this->getRoles(), true);
    }

    /**
     * Get the permission level label for display
     */
    public function getPermissionLevel(): string
    {
        // Admin users always show Full
        if ($this->isAdmin()) {
            return 'Admin';
        }
        
        return \App\Enum\Permission::getPermissionLevel($this->getPermissions());
    }

    /**
     * Get all permissions (stored in DB, or empty if using defaults)
     */
    public function getPermissions(): array
    {
        return $this->permissions ?? [];
    }

    /**
     * Set permissions
     * @param list<string> $permissions
     */
    public function setPermissions(array $permissions): static
    {
        $this->permissions = array_values(array_unique($permissions));
        return $this;
    }

    /**
     * Add a single permission
     */
    public function addPermission(string $permission): static
    {
        if (!in_array($permission, $this->permissions ?? [], true)) {
            $this->permissions[] = $permission;
        }
        return $this;
    }

    /**
     * Remove a single permission
     */
    public function removePermission(string $permission): static
    {
        if (($key = array_search($permission, $this->permissions ?? [], true)) !== false) {
            unset($this->permissions[$key]);
            $this->permissions = array_values($this->permissions);
        }
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function __serialize(): array
    {
        // Don't include password in serialization to avoid security issues
        // The password is already properly hashed by Symfony's password hasher
        return [
            'id' => $this->id,
            'email' => $this->email,
            'username' => $this->username,
            'roles' => $this->roles,
            'permissions' => $this->permissions,
        ];
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

      public function getExternalId(): ?int
    {
        return $this->externalId;
    }

    public function setExternalId(?int $externalId): static
    {
        $this->externalId = $externalId;
        return $this;
    }

    /**
     * @return Collection<int, Contrat>
     */
    public function getContrats(): Collection
    {
        return $this->contrats;
    }

    public function addContrat(Contrat $contrat): static
    {
        if (!$this->contrats->contains($contrat)) {
            $this->contrats->add($contrat);
            $contrat->setUser($this);
        }

        return $this;
    }

    public function removeContrat(Contrat $contrat): static
    {
        if ($this->contrats->removeElement($contrat)) {
            // set the owning side to null (unless already changed)
            if ($contrat->getUser() === $this) {
                $contrat->setUser(null);
            }
        }

        return $this;
    }
    
}