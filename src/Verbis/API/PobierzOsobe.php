<?php

namespace App\Verbis\API;

use App\Database\Entity\Student;
use App\Verbis\VerbisService;
use Laminas\Soap\Client;
use function ceil;
use function count;
use function end;
use function is_array;
use function rtrim;

final class PobierzOsobe
{
    private const string OSOBA_SERVICE_URL = '/dziekanat/Student';
    private const string STUDIA_OSOBY_URL = '/dziekanat/StudiaStudenta';
    private const string SLOWNIK_URL = '/dziekanat/Slownik';

    public function __construct(
        private readonly string $apiUrl,
        private readonly VerbisService $verbisService,
    ) {}

    public function zablokujOsobe(string $email): mixed
    {
        $client = new Client(
            rtrim($this->apiUrl, '/') . self::OSOBA_SERVICE_URL,
            VerbisService::getOptions()
        );

        $parameters = [
            'kryteria' => [
                'email' => $email,
            ],
        ];

        $this->verbisService->login($client);
        $response = $client->call('getStudent', [$parameters]);
        $this->verbisService->logout($client);

        return $response;
    }

    public function pobierzNrAlbumu(Student $student): ?int
    {
        $client = new Client(
            rtrim($this->apiUrl, '/') . self::STUDIA_OSOBY_URL,
            VerbisService::getOptions()
        );

        $idOsoby = $this->pobierzOsobe($student)->id;

        $parameters = [
            'id' => $idOsoby,
        ];

        $this->verbisService->login($client);
        $response = $client->call('getId', [$parameters]);
        $this->verbisService->logout($client);

        if (!isset($response->return)) {
            throw new \RuntimeException('Nie znaleziono osoby w Verbisie');
        }

        if (!is_array($response->return)) {
            $idTury = [$response->return];
        }

        if (is_array($response->return)) {
            $idTury = $response->return;
        }

        $parameters = [
            'idTury' => [
                'idStudenta' => $idOsoby,
                'nrTury' => $idTury[0]->nrTury,
            ]
        ];

        $this->verbisService->login($client);
        $response = $client->call('getTura', [$parameters]);
        $this->verbisService->logout($client);
        if (!isset($response->return)) {
            throw new \RuntimeException('Nie znaleziono osoby w Verbisie');
        }

        if (is_array($response->return)) {
            throw new \RuntimeException('Znaleziono więcej niż jedną osobę');
        }

        return $response->return->nrAlbumu;
    }

    public function pobierzOsobe(Student $student): ?object
    {
        $client = new Client(
            rtrim($this->apiUrl, '/') . self::OSOBA_SERVICE_URL,
            VerbisService::getOptions()
        );

        $parameters = [
            'kryteria' => [
                'email' => $student->email,

            ],
        ];

        $this->verbisService->login($client);
        $response = $client->call('getStudent', [$parameters]);
        $this->verbisService->logout($client);

        if (!isset($response->return)) {
            throw new \RuntimeException('Nie znaleziono osoby w Verbisie');
        }

        if (is_array($response->return)) {
            throw new \RuntimeException('Znaleziono więcej niż jedną osobę');
        }

        return $response->return;
    }

    public function pobierzOrzeczenie(Student $student): ?object
    {
        $client = new Client(
            rtrim($this->apiUrl, '/') . self::OSOBA_SERVICE_URL,
            VerbisService::getOptions()
        );

        $idOsoby = $this->pobierzOsobe($student)->id;

        $parameters = [
            'id' => $idOsoby,
        ];

        $this->verbisService->login($client);
        $response = $client->call('getOrzeczenie', [$parameters]);
        $this->verbisService->logout($client);

        if (!isset($response->return)) {
            throw new \RuntimeException('Nie znaleziono dodanych orzeczeń w Verbisie');
        }

        if (!is_array($response->return)) {
            $idTury = [$response->return];
        }

        if (is_array($response->return)) {
            $idTury = $response->return;
        }

        return $response->return;
    }

    public function pobierzKierunek(Student $student): ?object
    {
        $client = new Client(
            rtrim($this->apiUrl, '/') . self::SLOWNIK_URL,
            VerbisService::getOptions()
        );

        $turaStudiow = $this->pobierzTure($student);

        $ostatniSemestr = end($turaStudiow->semestryTury);

        $parameters = [
            'id' => $ostatniSemestr->idKierunku,
        ];

        $this->verbisService->login($client);
        $response = $client->call('getKierunek', [$parameters]);
        $this->verbisService->logout($client);

        if (!isset($response->return)) {
            throw new \RuntimeException('Nie znaleziono osoby w Verbisie');
        }

        if (!is_array($response->return)) {
            $idTury = [$response->return];
        }

        if (is_array($response->return)) {
            $idTury = $response->return;
        }

        return $response->return;
    }

    public function pobierzTure(Student $student): ?object
    {
        $client = new Client(
            rtrim($this->apiUrl, '/') . self::STUDIA_OSOBY_URL,
            VerbisService::getOptions()
        );

        $idOsoby = $this->pobierzOsobe($student)->idOsoby;

        $parameters = [
            'id' => $idOsoby,
        ];

        $this->verbisService->login($client);
        $response = $client->call('getTura', [$parameters]);
        $this->verbisService->logout($client);

        if (!isset($response->return)) {
            throw new \RuntimeException('Nie znaleziono osoby w Verbisie');
        }

        if (!is_array($response->return)) {
            $idTury = [$response->return];
        }

        if (is_array($response->return)) {
            $idTury = $response->return;
        }

        $ostatniSemestr = end($idTury);

        $parameters = [
            'idTury' => [
                'idStudenta' => $idOsoby,
                'nrTury' => $ostatniSemestr->nrTury,
            ]
        ];

        $this->verbisService->login($client);
        $response = $client->call('getTura', [$parameters]);
        $this->verbisService->logout($client);
        if (!isset($response->return)) {
            throw new \RuntimeException('Nie znaleziono osoby w Verbisie');
        }

        if (is_array($response->return)) {
            throw new \RuntimeException('Znaleziono więcej niż jedną osobę');
        }

        return $response->return;
    }

    public function pobierzWydzial2(Student $student): ?object
    {
        $client = new Client(
            rtrim($this->apiUrl, '/') . self::SLOWNIK_URL,
            VerbisService::getOptions()
        );

        $turaStudiow = $this->pobierzTure($student);

        $idWydzialu = $turaStudiow->idWydzialu;

        $parameters = [
            'idWydzialu' => $idWydzialu,
        ];

        $this->verbisService->login($client);
        $response = $client->call('getWydzial', [$parameters]);
        $this->verbisService->logout($client);

        if (!isset($response->return)) {
            throw new \RuntimeException('Nie znaleziono osoby w Verbisie');
        }

        if (!is_array($response->return)) {
            $idTury = [$response->return];
        }

        if (is_array($response->return)) {
            $idTury = $response->return;
        }

        return $response->return;
    }

    public function pobierzLiczbeSemestrowTury(Student $student): ?int
    {
        $client = new Client(
            rtrim($this->apiUrl, '/') . self::STUDIA_OSOBY_URL,
            VerbisService::getOptions()
        );

        $idOsoby = $this->pobierzOsobe($student)->idOsoby;

        $parameters = [
            'idStudenta' => $idOsoby,
        ];

        $this->verbisService->login($client);
        $response = $client->call('getTura', [$parameters]);
        $this->verbisService->logout($client);

        if (!isset($response->return)) {
            throw new \RuntimeException('Nie znaleziono osoby w Verbisie');
        }

        if (!is_array($response->return)) {
            $idTury = [$response->return];
        }

        if (is_array($response->return)) {
            $idTury = $response->return;
        }

        $ostatniSemestr = end($idTury);

        $parameters = [
            'idTury' => [
                'idStudenta' => $idOsoby,
                'nrTury' => $ostatniSemestr->nrTury,
            ]
        ];

        $this->verbisService->login($client);
        $response = $client->call('getTura', [$parameters]);
        $this->verbisService->logout($client);
        if (!isset($response->return)) {
            throw new \RuntimeException('Nie znaleziono osoby w Verbisie');
        }

        if (is_array($response->return)) {
            throw new \RuntimeException('Znaleziono więcej niż jedną osobę');
        }

        $liczbaSemestrow = count($response->return->semestryTury);

        return $liczbaSemestrow;
    }

    public function pobierzRokStudiow(Student $student): ?int
    {
        $client = new Client(
            rtrim($this->apiUrl, '/') . self::STUDIA_OSOBY_URL,
            VerbisService::getOptions()
        );

        $idOsoby = $this->pobierzOsobe($student)->idOsoby;

        $parameters = [
            'idOsoby' => $idOsoby,
        ];

        $this->verbisService->login($client);
        $response = $client->call('getIdTur', [$parameters]);
        $this->verbisService->logout($client);

        if (!isset($response->return)) {
            throw new \RuntimeException('Nie znaleziono osoby w Verbisie');
        }

        if (!is_array($response->return)) {
            $idTury = [$response->return];
        }

        if (is_array($response->return)) {
            $idTury = $response->return;
        }

        $ostatniSemestr = end($idTury);

        $parameters = [
            'idTury' => [
                'idStudenta' => $idOsoby,
                'nrTury' => $ostatniSemestr->nrTury,
            ]
        ];

        $this->verbisService->login($client);
        $response = $client->call('getTura', [$parameters]);
        $this->verbisService->logout($client);
        if (!isset($response->return)) {
            throw new \RuntimeException('Nie znaleziono osoby w Verbisie');
        }

        if (is_array($response->return)) {
            throw new \RuntimeException('Znaleziono więcej niż jedną osobę');
        }

        $liczbaSemestrow = count($response->return->semestryTury);

        return (int)ceil($liczbaSemestrow / 2);
    }

}
