<?php

declare(strict_types=1);

namespace App\Security\Student;

use App\Core\Student\StudentManager;
use App\Core\Student\StudentRepository;
use App\Database\Entity\Student;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function str_ends_with;
use function strtolower;

/**
 * Class StudentChecker
 * @package App\Security\Student
 */
readonly class StudentChecker implements UserCheckerInterface
{
    public function __construct(private StudentRepository $studentRepository, private StudentManager $studentManager, private TranslatorInterface $translator) {}

    /**
     * @param UserInterface $user
     * @return void
     */
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof Student) {
            return;
        }

        if (false === $user->isActive) {
            throw new CustomUserMessageAccountStatusException($this->translator->trans('Twoje konto jest zablokowane'));
        }

        $email = strtolower($user->email);
        $azureId = $user->azureId;
        $allowedDomain1 = 'students.vizja.pl';
        $allowedDomain2 = 'vizja.pl';

        if (!str_ends_with($email, '@' . $allowedDomain1) && !str_ends_with($email, '@' . $allowedDomain2) && $azureId != null) {
            $this->studentManager->delete($user);
            throw new CustomUserMessageAccountStatusException(
                sprintf(
                    'Można zalogować się tylko kontem Office 365 w organizacji Uniwersytetu VIZJA w domenie %s lub %s.',
                    $allowedDomain1,
                    $allowedDomain2
                )
            );
        }
    }

    /**
     * @param UserInterface $user
     * @return void
     */
    public function checkPostAuth(UserInterface $user): void
    {
        if (!$user instanceof Student) {
            return;
        }

        if (false === $user->isActive) {
            throw new CustomUserMessageAccountStatusException($this->translator->trans('Twoje konto jest zablokowane'));
        }
    }
}
