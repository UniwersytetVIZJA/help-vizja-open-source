<?php

declare(strict_types=1);

namespace App\Security\Student;

use App\Core\Student\StudentRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class SecurityUserProvider
 * @package App\Security\User
 */
readonly class SecurityStudentProvider implements UserProviderInterface
{
    /**
     * SecurityUserProvider constructor
     * @param StudentRepository $studentRepository
     */
    public function __construct(
        private StudentRepository $studentRepository
    ) {}

    /**
     * @param string $identifier
     * @return UserInterface
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $student = $this->studentRepository->getOneByEmail($identifier);
        if ($student) {
            return new SecurityStudent($student);
        }

        throw new BadCredentialsException();
    }

    /**
     * @param UserInterface $student
     * @return UserInterface
     */
    public function refreshUser(UserInterface $student): UserInterface
    {
        if (!$student instanceof SecurityStudent) {
            throw new UnsupportedUserException();
        }

        $reloadedStudent = $this->studentRepository->getOneById($student->getDomainUser()->id);

        return new SecurityStudent($reloadedStudent);
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass(string $class): bool
    {
        return $class === SecurityStudent::class;
    }
}
