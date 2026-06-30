<?php

namespace App\Security\Voter;

use App\Database\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserPhotoVoter extends Voter {
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === 'view_avatar' && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        if (in_array('ROLE_ADMIN', $currentUser->getRoles(), true)) {
            return true;
        }

        return $currentUser->id === $subject->id;
    }
}
