<?php

namespace App\Security\Voter;

use App\Database\Entity\Registration;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class RegistrationVoter extends Voter
{

    public const string VIEW = 'REGISTRATION_VIEW';

    /**
     * @param string $attribute
     * @param $subject
     * @return bool
     */
    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::VIEW && $subject instanceof Registration;
    }

    /**
     * @param string $attribute
     * @param $subject
     * @param TokenInterface $token
     * @return bool
     */
    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof UserInterface) return false;

        /** @var Registration $registration */
        $registration = $subject;
        $ownerUser = $registration->specialist;

        if (!$ownerUser) {
            return false;
        }

        return (string)$ownerUser->getId() === (string)$user->getId();
    }
}
