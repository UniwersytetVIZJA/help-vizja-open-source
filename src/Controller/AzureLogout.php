<?php

declare(strict_types=1);

namespace App\Controller;

use App\Graph\GraphEnum;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use function urlencode;

class AzureLogout extends AbstractController
{
    /**
     * @param TokenStorageInterface $tokenStorage
     * @param RequestStack $requestStack
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(
        private readonly TokenStorageInterface $tokenStorage,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    /**
     * Wylogowuje administratora z aplikacji oraz z Azure Active Directory.
     *
     * Metoda usuwa token uwierzytelnienia, czyści sesję użytkownika
     * (w tym tokeny Microsoft Graph) i przekierowuje do punktu wylogowania
     * Azure AD z powrotem do strony głównej panelu administracyjnego.
     *
     * @return RedirectResponse Przekierowanie do wylogowania Azure AD
     */
    #[Route('/admin/logout', name: 'admin_logout_full')]
    public function logout(): RedirectResponse
    {
        $this->tokenStorage->setToken(null);

        $session = $this->requestStack->getSession();

        if ($session) {
            $session->remove(GraphEnum::ACCESS_TOKEN->value);
            $session->remove(GraphEnum::REFRESH_TOKEN->value);
            $session->invalidate();
        }

        $postLogoutUrl = $this->urlGenerator->generate('admin_app_homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $msLogoutUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/logout'
            . '?post_logout_redirect_uri=' . urlencode($postLogoutUrl);

        return new RedirectResponse($msLogoutUrl);
    }

    /**
     * Wylogowuje użytkownika z aplikacji oraz z Azure Active Directory.
     *
     * Metoda usuwa lokalne dane uwierzytelnienia, czyści sesję użytkownika
     * i przekierowuje do punktu wylogowania Azure AD z powrotem do strony głównej.
     *
     * @return RedirectResponse Przekierowanie do wylogowania Azure AD
     */
    #[Route('/logout', name: 'logout_full')]
    public function logoutStudent(): RedirectResponse
    {
        $this->tokenStorage->setToken(null);

        $session = $this->requestStack->getSession();

        if ($session) {
            $session->remove(GraphEnum::ACCESS_TOKEN->value);
            $session->remove(GraphEnum::REFRESH_TOKEN->value);
            $session->invalidate();
        }

        $postLogoutUrl = $this->urlGenerator->generate('app_homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $msLogoutUrl = 'https://login.microsoftonline.com/common/oauth2/v2.0/logout'
            . '?post_logout_redirect_uri=' . urlencode($postLogoutUrl);

        return new RedirectResponse($msLogoutUrl);
    }

    #[Route('/gosc/logout', name: 'logout_guest')]
    public function logoutGuest(): RedirectResponse
    {
        $session = $this->requestStack->getSession();

        if ($session && $session->isStarted()) {
            $session->invalidate();
        }

        $logoutUrl = $this->urlGenerator->generate('guest_app_homepage', [], UrlGeneratorInterface::ABSOLUTE_URL);

        return new RedirectResponse($logoutUrl);
    }
}
