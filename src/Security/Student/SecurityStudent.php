<?php

declare(strict_types=1);

namespace App\Security\Student;

use App\Database\Entity\Student;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * Class SecurityStudent
 * @package App\Security\Student
 */
readonly class SecurityStudent implements PasswordAuthenticatedUserInterface, UserInterface
{
    /**
     * SecurityStudent constructor
     * @param Security $security
     * @param Student $student
     */
    public function __construct(private readonly Security $security, private Student $student) {}

    /**
     * @return Student|null
     */
    public function getStudent(): ?Student
    {
        $user = $this->security->getUser();

        return $user instanceof Student ? $user : null;
    }

    /**
     * @return void
     */
    public function eraseCredentials(): void {}

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->student->id;
    }

    /**
     * @return Student
     */
    public function getDomainUser(): Student
    {
        return $this->student;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->student->getPassword();
    }

    /**
     * @return array
     */
    public function getRoles(): array
    {
        return $this->student->getRoles();
    }

    /**
     * @return string
     */
    public function getUserIdentifier(): string
    {
        return $this->student->id;
    }

    /**
     * @return string|null
     */
    public function getFirstName(): ?string
    {
        return $this->student->firstName;
    }

    /**
     * @return string|null
     */
    public function getLastName(): ?string
    {
        return $this->student->lastName;
    }

    /**
     * @return string|null
     */
    public function getInitials(): ?string
    {
        return $this->student->getInitials();
    }

    /**
     * @return bool
     */
    public function securityActive(): bool
    {
        return $this->student->isActive;
    }

    /**
     * @return int|null
     */
    public function getAlbumNumber(): ?int
    {
        return $this->student->albumNumber;
    }
}
