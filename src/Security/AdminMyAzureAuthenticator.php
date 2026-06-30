<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\BasePersister;
use App\Core\User\UserRepository;
use App\Database\Entity\Student;
use App\Database\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use TheNetworg\OAuth2\Client\Provider\AzureResourceOwner;
use function time;

/**
 * Class MyAzureAuthenticator
 * @package App\Security
 */
class AdminMyAzureAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private RequestStack $requestStack;
    private UserRepository $userRepository;
    private BasePersister $basePersister;

    /**
     * MyAzureAuthenticator constructor
     * @param ClientRegistry $clientRegistry
     * @param EntityManagerInterface $entityManager
     * @param RouterInterface $router
     * @param RequestStack $requestStack
     * @param UserRepository $userRepository
     * @param BasePersister $basePersister
     */
    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        RouterInterface $router,
        RequestStack $requestStack,
        UserRepository $userRepository, BasePersister $basePersister,
    ) {
        $this->clientRegistry = $clientRegistry;
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->requestStack = $requestStack;
        $this->userRepository = $userRepository;
        $this->basePersister = $basePersister;
    }

    /**
     * @param Request $request
     * @return Passport
     */
    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient('azure_admin');
        $accessToken = $this->fetchAccessToken($client);

        $session = $request->getSession();
        $session->set('ms_access_token', $accessToken->getToken());

        if ($accessToken->getExpires()) {
            $session->set('ms_access_token_expires_at', time() + (int) $accessToken->getExpires());
        }

        /** @var AzureResourceOwner $azureUser */
        $azureUser = $client->fetchUserFromToken($accessToken);

        $azureData = $azureUser->toArray();

        $email = $azureUser->getUpn()
            ?: ($azureData['email'] ?? $azureData['mail'] ?? $azureData['userPrincipalName'] ?? null);

        $azureId = $azureUser->getId();
        $identifier = $email ?? $azureId;

        $firstName = $azureData['givenName']
            ?? $azureData['firstName']
            ?? null;

        $lastName = $azureData['surname']
            ?? $azureData['lastName']
            ?? null;

        return new SelfValidatingPassport(
            new UserBadge($identifier, function () use ($email, $azureId, $firstName, $lastName) {
                $user = $this->userRepository->findOneBy(['email' => $email]);

                if (!$user instanceof User) {
                    throw new CustomUserMessageAuthenticationException('Konto o podanym adresie e-mail nie istnieje.');
                }

                $changed = false;

                if ($user->azureId !== $azureId) {
                    $user->azureId = $azureId;
                    $changed = true;
                }

                if ($firstName && $user->firstName !== $firstName) {
                    $user->firstName = $firstName;
                    $changed = true;
                }

                if ($lastName && $user->lastName !== $lastName) {
                    $user->lastName = $lastName;
                    $changed = true;
                }

                if ($changed) {
                    $this->basePersister->update($user, true);
                }

                return $user;
            })
        );
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return Response|null
     */
    public function onAuthenticationFailure(
        Request $request, AuthenticationException $exception): ?Response {
        $request->getSession()->set(
            'azure_error',
            strtr(
                $exception->getMessageKey(),
                $exception->getMessageData()
            )
        );

        return new RedirectResponse(
            $this->router->generate('admin_security_azure_error')
        );
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $firewallName
     * @return Response|null
     */
    public function
    onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName
    ): ?Response {
        return new RedirectResponse($this->router->generate('admin_dashboard'));
    }

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            '/admin/security/azure/login',
            Response::HTTP_TEMPORARY_REDIRECT
        );
    }

    /**
     * @param Request $request
     * @return bool|null
     */
    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'admin_security_azure_check';
    }
}
