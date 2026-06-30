<?php

declare(strict_types=1);

namespace App\Security;

use App\Core\Student\StudentManager;
use App\Database\Entity\Student;
use App\Graph\GraphEnum;
use App\Graph\GraphTokenManager;
use App\Graph\GraphTokenSession;
use App\Verbis\API\PobierzOsobe;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use KnpU\OAuth2ClientBundle\Security\Authenticator\OAuth2Authenticator;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use TheNetworg\OAuth2\Client\Provider\AzureResourceOwner;
use Twig\Environment;
use function property_exists;
use function str_ends_with;
use function strtolower;
use function time;

/**
 * Class MyAzureAuthenticator
 * @package App\Security
 */
class MyAzureAuthenticator extends OAuth2Authenticator implements AuthenticationEntrypointInterface
{
    private ClientRegistry $clientRegistry;
    private EntityManagerInterface $entityManager;
    private RouterInterface $router;
    private GraphTokenSession $graphTokenSession;
    private HttpClientInterface $httpClient;
    private GraphTokenManager $graphTokenManager;
    private PobierzOsobe $pobierzOsobe;
    private StudentManager $studentManager;
    private TranslatorInterface $translator;
    private Environment $environment;

    /**
     * MyAzureAuthenticator constructor
     * @param ClientRegistry $clientRegistry
     * @param EntityManagerInterface $entityManager
     * @param RouterInterface $router
     * @param GraphTokenSession $graphTokenSession
     * @param HttpClientInterface $httpClient
     * @param GraphTokenManager $graphTokenManager
     * @param PobierzOsobe $pobierzOsobe
     * @param StudentManager $studentManager
     * @param TranslatorInterface $translator
     * @param Environment $environment
     */
    public function __construct(
        ClientRegistry $clientRegistry,
        EntityManagerInterface $entityManager,
        RouterInterface $router, GraphTokenSession $graphTokenSession, HttpClientInterface $httpClient, GraphTokenManager $graphTokenManager, PobierzOsobe $pobierzOsobe, StudentManager $studentManager, TranslatorInterface $translator, Environment $environment,
    ) {
        $this->entityManager = $entityManager;
        $this->router = $router;
        $this->clientRegistry = $clientRegistry;
        $this->graphTokenSession = $graphTokenSession;
        $this->httpClient = $httpClient;
        $this->graphTokenManager = $graphTokenManager;
        $this->pobierzOsobe = $pobierzOsobe;
        $this->studentManager = $studentManager;
        $this->translator = $translator;
        $this->environment = $environment;
    }

    /**
     * @param Request $request
     * @return Passport
     */
    public function authenticate(Request $request): Passport
    {
        $client = $this->clientRegistry->getClient(GraphEnum::CLIENT_STUDENT->value);
        $accessToken = $this->fetchAccessToken($client);

        $session = $request->getSession();
        $session->set(GraphEnum::ACCESS_TOKEN->value, $accessToken->getToken());
        if ($accessToken->getExpires()) {
            $session->set(GraphEnum::ACCESS_TOKEN_EXPIRES->value, time() + (int)$accessToken->getExpires());
        }

        $token = $this->graphTokenManager->getValidAccessToken(GraphEnum::CLIENT_STUDENT->value);
        $response = $this->httpClient->request('GET', 'https://graph.microsoft.com/v1.0/me', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);

        $data = $response->toArray();

        $session?->set((GraphEnum::ACCESS_TOKEN->value), $accessToken->getToken());

        $this->graphTokenSession->saveToken(
            $accessToken->getToken(),
            $accessToken->getExpires(),
            $accessToken->getRefreshToken(),
        );

        /** @var AzureResourceOwner $azureUser */
        $azureUser = $client->fetchUserFromToken($accessToken);

        $azureId = $azureUser->getId();
        $email = $azureUser->getUpn() ?: ($azureUser->toArray()['email'] ?? null);
        $firstName = $data['givenName'];
        $lastName = $data['surname'];

        $data = $azureUser->toArray();
        $fullName = $data['name'] ?? null;

        $identifier = $email ?? $azureId;

        return new SelfValidatingPassport(
            new UserBadge($identifier, function () use ($azureId, $email, $firstName, $lastName) {
                $repo = $this->entityManager->getRepository(Student::class);

                $student = $repo->findOneBy(['azureId' => $azureId]);

                if (!$student instanceof Student && $email) {
                    $student = $repo->findOneBy(['email' => $email]);
                }

                try {
                    $verbis = $this->pobierzOsobe->zablokujOsobe($email);
                } catch (\Throwable $e) {
                    throw new CustomUserMessageAuthenticationException(
                        $this->translator->trans('Konto Office 365 nie jest powiązane z Verbisem')
                    );
                }

                if (!property_exists($verbis, 'return')) {
                    throw new CustomUserMessageAuthenticationException(
                        $this->translator->trans('Konto Office 365 nie jest powiązane z Verbisem')
                    );
                }

                if (!$student instanceof Student) {
                    $student = new Student();
                    $student->azureId = $azureId;
                    $student->email = $email;
                    $student->firstName = $firstName;
                    $student->lastName = $lastName;
                    $student->isActive = true;
                    $student->roles = ['ROLE_STUDENT'];
                    $student->lastLogin = new \DateTimeImmutable();

                    $this->entityManager->persist($student);
                    $this->entityManager->flush();

                    $this->studentManager->updateDataViaVerbis($student);
                }

                $email = strtolower($student->email);
                $domain = 'vizja.pl';

                if (str_ends_with($email, '@' . $domain)) {
                    $student->roles = ['ROLE_PRACOWNIK'];
                }

                if ($student instanceof Student) {
                    $student->lastLogin = new \DateTimeImmutable();

                    $this->entityManager->persist($student);
                    $this->entityManager->flush();
                }

                if ($student->azureId === null) {
                    $student->azureId = $azureId;
                    $this->entityManager->flush();
                }

                return $student;
            }),
        );
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return Response|null
     */
    public function onAuthenticationFailure(
        Request $request, AuthenticationException $exception,
    ): ?Response {
        $request->getSession()->set(
            'azure_error',
            strtr(
                $exception->getMessageKey(),
                $exception->getMessageData(),
            ),
        );

        if ($exception instanceof CustomUserMessageAuthenticationException) {
            return new RedirectResponse(
                $this->router->generate('security_azure_error'),
            );
        }

        return new RedirectResponse(
            $this->router->generate('security_azure_error'),
        );
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $firewallName
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->router->generate('student_dashboard'));
    }

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return Response
     */
    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse(
            '/security/azure/login',
            Response::HTTP_TEMPORARY_REDIRECT,
        );
    }

    /**
     * @param Request $request
     * @return bool|null
     */
    public function supports(Request $request): ?bool
    {
        return $request->attributes->get('_route') === 'security_azure_check';
    }

}
