<?php

declare(strict_types=1);

namespace App\Security\User;

use App\Database\Entity\Student;
use App\Database\Entity\User;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class SecurityUser
 * @package App\Security\User
 */
readonly class SecurityUser implements PasswordAuthenticatedUserInterface, UserInterface
{
    /**
     * SecurityUser constructor
     * @param User $user
     * @param Student $student
     */

    public function __construct(
        private readonly User $user, private Student $student
    ) {}

    /**
     * @return void
     */
    public function eraseCredentials(): void {}

    /**
     * @return User
     */
    public function getDomainUser(): User
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->user->getPassword();
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->user->id;
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->user->roles;
    }

    /**
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return $this->user->email;
    }

    /**
     * @return string
     */
    public function getFirstName(): string
    {
        return $this->user->firstName;
    }

    /**
     * @return string
     */
    public function getLastName(): string
    {
        return $this->user->lastName;
    }

    /**
     * @return string|null
     */
    public function getInitials(): ?string
    {
        return $this->user->getInitials();
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->user->getStatus();
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->user->isActive;
    }

}
