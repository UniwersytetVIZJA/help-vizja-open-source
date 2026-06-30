<?php

declare(strict_types=1);

namespace App\Security;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

/**
 * Class LoginSuccessHandler
 * @package App\Security
 */
class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    /**
     * LoginSuccessHandler constructor
     * @param RouterInterface $router
     */
    public function __construct(
        private RouterInterface $router, private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @return RedirectResponse
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token): RedirectResponse
    {
        $roles = $token->getRoleNames();
        $user = $token->getUser();

        if (in_array('ROLE_GOSC', $roles, true)) {
            $user->lastLogin = new \DateTimeImmutable();
            $this->entityManager->flush();

            return new RedirectResponse($this->router->generate('guest_dashboard'));
        }

        return new RedirectResponse($this->router->generate('student_dashboard'));
    }
}
