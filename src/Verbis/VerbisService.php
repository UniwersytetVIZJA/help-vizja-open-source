<?php

namespace App\Verbis;

use Laminas\Soap\Client;

final class VerbisService
{
    public function __construct(
        private readonly string $apiUrl,
        private readonly string $login,
        private readonly string $password,
    ) {}

    private static array $options = [
        'cache_wsdl' => false,
        'connection_timeout' => 5,
        'keep_alive' => false,
        'soap_version' => 1,
    ];

    /**
     * @return array
     */
    public static function getOptions(): array
    {
        $options = self::$options;
        $options['stream_context'] = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]);

        return $options;
    }

    /**
     * @param Client $client
     * @return bool
     */
    public function login(Client $client): bool
    {
        $parameters = [
            'login' => $this->login,
            'password' => $this->password,
        ];

        $result = $client->call('login', [$parameters]);

        return $result->result ?? false;
    }

    public function logout(Client $client): void
    {
        $client->call('logout');
    }
}
