<?php

namespace App\Graph;

use App\Core\BasePersister;
use App\Database\Entity\User;
use App\Database\Repository\UserRepository;
use App\Security\MyAzureAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use function dump;
use function rawurldecode;

final readonly class GraphCreateUserService
{
    /**
     * @param HttpClientInterface $http
     * @param GraphTokenSession $graphTokenSession
     * @param MyAzureAuthenticator $azureAuthenticator
     * @param MyAzureAuthenticator $myAzureAuthenticator
     * @param GraphTokenManager $graphTokenManager
     */
    public function __construct(
        private HttpClientInterface $http,
        private GraphTokenSession $graphTokenSession,
        private readonly MyAzureAuthenticator $azureAuthenticator, private MyAzureAuthenticator $myAzureAuthenticator, private GraphTokenManager $graphTokenManager, private EntityManagerInterface $entityManager, private BasePersister $basePersister, private UserRepository $userRepository,
    ) {}

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws DecodingExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function createUser(string $userUpn): ?array
    {
        $token = $this->graphTokenManager->getValidAccessToken(GraphEnum::CLIENT_ADMIN->value);

        $response = $this->http->request(
            'GET',
            'https://graph.microsoft.com/v1.0/users/' . rawurlencode($userUpn)
            . '?$select=id,userPrincipalName,mail,givenName,surname,displayName',
            [
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                    'Accept' => 'application/json',
                ],
            ]
        );

        if ($response->getStatusCode() !== 200) {
            return null;
        }

        $data = $response->toArray(false);


        $azureId = $data['id'] ?? null;
        $firstName = $data['givenName'] ?? '';
        $lastName = $data['surname'] ?? '';
        $email = $data['mail'] ?? $data['userPrincipalName'] ?? $userUpn;

        $user = $this->userRepository->findOneBy([
            'email' => $email,
        ]);

        if (!$user) {
            $user = $this->userRepository->findOneBy([
                'azureId' => $azureId,
            ]);
        }

        if (!$user) {
            return null;
        }

        $user->azureId = $azureId;
        $user->firstName = $firstName;
        $user->lastName = $lastName;

        $this->basePersister->update($user, true);


        return [
            'id' => $data['id'] ?? null,
            'upn' => $data['userPrincipalName'] ?? null,
            'email' => $data['mail'] ?? $data['userPrincipalName'] ?? null,
            'firstName' => $data['givenName'] ?? null,
            'lastName' => $data['surname'] ?? null,
            'displayName' => $data['displayName'] ?? null,
        ];
    }

}
