<?php

namespace App\Security\Voter;

use App\Database\Entity\RegisteredStudent;
use App\Database\Entity\Registration;
use App\Database\Entity\Student;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class RegistrationDetailsVoter extends Voter
{
    public const ACCESS = 'REGISTRATION_ACCESS';

    /**
     * @param string $attribute
     * @param mixed $subject
     * @return bool
     */
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::ACCESS && $subject instanceof Registration;
    }

    /**
     * @param string $attribute
     * @param mixed $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof Student) return false;

        /** @var Registration $registration */
        $registration = $subject;

        foreach ($registration->registeredStudents->toArray() as $registeredStudent) {
            /** @var RegisteredStudent $registeredStudent */
            if ($registeredStudent->student === $user) {
                return true;
            }
        }

        return false;
    }
}
