<?php

namespace App\Security\Voter;

use App\Database\Entity\Application;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class ApplicationVoter extends Voter
{
    public const string VIEW = 'APPLICATION_VIEW';
    public const string EDIT = 'APPLICATION_EDIT';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true)
            && $subject instanceof Application;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            return false;
        }

        /** @var Application $application */
        $application = $subject;

        return match ($attribute) {
            self::VIEW => $this->canView($application, $user),
            self::EDIT => $this->canEdit($application, $user),
            default => false,
        };
    }

    private function canView(Application $application, UserInterface $user): bool
    {
        return $this->isOwner($application, $user);
    }

    private function isOwner(Application $application, UserInterface $user): bool
    {
        $ownerUser = $application->student;

        if (!$ownerUser) {
            return false;
        }

        return (string)$ownerUser->getId() === (string)$user->getId();
    }

    private function canEdit(Application $application, UserInterface $user): bool
    {
        if (!$this->isOwner($application, $user)) {
            return false;
        }

        return $application->applicationNumber === null;
    }
}

