<?php

declare(strict_types=1);

namespace App\Database\Entity;

use App\Database\Repository\UserRepository;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Symfony\Component\Security\Core\User\UserInterface;
use function array_unique;
use function array_values;
use function mb_strtoupper;
use function mb_substr;

/**
 * Class User
 * @package App\Database\Entity
 */
#[Entity(repositoryClass: UserRepository::class)]
#[Table(name: 'user')]
class User extends BaseEntity implements UserInterface
{
    /**
     * @var string|null
     */
    #[Column(type: 'string', length: 100, nullable: true)]
    public ?string $azureId = null {
        get {
            return $this->azureId;
        }
        set {
            $this->azureId = $value;
        }
    }

    /**
     * @var string
     */
    #[Column(type: 'text', length: 100)]
    public string $email {
        get {
            return $this->email;
        }
        set {
            $this->email = $value;
        }
    }

    /**
     * @var bool
     */
    #[Column(type: 'boolean')]
    public bool $isActive {
        get {
            return $this->isActive;
        }
        set {
            $this->isActive = $value;
        }
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->isActive ? 'Tak' : 'Nie';
    }

    /**
     * @var string
     */
    #[Column(type: 'text', length: 100)]
    public ?string $firstName = '' {
        get {
            return $this->firstName;
        }
        set {
            $this->firstName = $value;
        }
    }

    /**
     * @var string
     */
    #[Column(type: 'text', length: 100)]
    public ?string $lastName = '' {
        get {
            return $this->lastName;
        }
        set {
            $this->lastName = $value;
        }
    }

    /**
     * @return string
     */
    #[Column(type: 'string', length: 1)]
    public function getInitials(): string
    {
        $firstInitial = mb_substr($this->firstName, 0, 1, 'UTF-8');
        $lastInitial = mb_substr($this->lastName, 0, 1, 'UTF-8');

        return mb_strtoupper($firstInitial . $lastInitial, 'UTF-8');
    }

    /**
     * @var array
     */
    #[Column(type: 'json')]
    public array $roles {
        get {
            $roles = $this->roles;

            return array_values(array_unique($roles));
        }
        set {
            $this->roles = array_values(array_unique($value));
        }
    }

    /**
     * @inheritDoc
     */
    public function eraseCredentials(): void
    {
        // TODO: Implement eraseCredentials() method.
    }

    /**
     * @inheritDoc
     */
    public function getUserIdentifier(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getRoles(): array
    {
        return $this->roles;
    }
}
