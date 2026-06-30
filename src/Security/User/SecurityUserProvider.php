<?php

declare(strict_types=1);

namespace App\Security\User;

use App\Core\User\UserRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

/**
 * Class SecurityUserProvider
 * @package App\Security\User
 */
readonly class SecurityUserProvider implements UserProviderInterface
{
    /**
     * SecurityUserProvider constructor
     * @param UserRepository $userRepository
     */
    public function __construct(
        private UserRepository $userRepository,
    ) {}

    /**
     * @param string $identifier
     * @return UserInterface
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->userRepository->getOneByEmail($identifier);

        if ($user) {
            return new SecurityUser($user);
        }

        throw new BadCredentialsException();
    }

    /**
     * @param UserInterface $user
     * @return UserInterface
     */
    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof SecurityUser) {
            throw new UnsupportedUserException();
        }

        $reloadedUser = $this->userRepository->getOneById($user->getDomainUser()->id);

        return new SecurityUser($reloadedUser);
    }

    /**
     * @param string $class
     * @return bool
     */
    public function supportsClass(string $class): bool
    {
        return $class === SecurityUser::class;
    }
}
