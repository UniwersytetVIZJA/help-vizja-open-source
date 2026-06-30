<?php

namespace App\Graph;

use App\Database\Entity\Dictionary\Item;
use App\Database\Entity\Student;
use App\Database\Entity\User;
use Exception;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class GraphCalendarService
{
    /**
     * @param HttpClientInterface $http
     * @param GraphTokenSession $graphTokenSession
     * @param GraphTokenManager $graphTokenManager
     * @param string $defaultTimeZone
     */
    public function __construct(
        private HttpClientInterface $http,
        private GraphTokenSession $graphTokenSession, private GraphTokenManager $graphTokenManager,
        private string $defaultTimeZone = 'Europe/Warsaw',
    ) {}

    /**
     * Tworzy wydarzenie w kalendarzu Microsoft Graph.
     *
     * Metoda wysyła żądanie do API Microsoft Graph w celu utworzenia
     * wydarzenia w kalendarzu zalogowanego użytkownika administracyjnego.
     *
     * @param array $payload Dane wydarzenia w formacie zgodnym z Microsoft Graph
     *
     * @return array Dane odpowiedzi API Graph (szczegóły utworzonego wydarzenia)
     *
     * @throws TransportExceptionInterface   Błąd transportu HTTP
     * @throws ServerExceptionInterface      Błąd po stronie serwera Microsoft Graph
     * @throws RedirectionExceptionInterface Błąd przekierowania
     * @throws DecodingExceptionInterface    Błąd dekodowania odpowiedzi
     * @throws ClientExceptionInterface      Błąd żądania klienta
     * @throws Exception                     Brak lub nieprawidłowy token dostępu
     */
    public function createEvent(array $payload): array
    {
        $token = $this->graphTokenManager->getValidAccessToken(GraphEnum::CLIENT_ADMIN->value);

        $response = $this->http->request('POST', 'https://graph.microsoft.com/v1.0/me/events', [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Prefer' => 'outlook.timezone="Europe/Warsaw"'
            ],
            'json' => $payload,
        ]);

        return $response->toArray(false);
    }

    /**
     * Aktualizuje istniejące wydarzenie w kalendarzu Microsoft Graph.
     *
     * Metoda wysyła żądanie PATCH do API Microsoft Graph w celu
     * zmiany danych wskazanego wydarzenia kalendarzowego.
     *
     * @param string $eventId Identyfikator wydarzenia w Microsoft Graph
     * @param array $payload Dane aktualizacji w formacie zgodnym z Microsoft Graph
     *
     * @return array Dane odpowiedzi API Graph po aktualizacji wydarzenia
     *
     * @throws TransportExceptionInterface   Błąd transportu HTTP
     * @throws ServerExceptionInterface      Błąd po stronie serwera Microsoft Graph
     * @throws RedirectionExceptionInterface Błąd przekierowania
     * @throws DecodingExceptionInterface    Błąd dekodowania odpowiedzi
     * @throws ClientExceptionInterface      Błąd żądania klienta
     */
    public function updateEvent(string $eventId, array $payload): array
    {
        $token = $this->graphTokenManager->getValidAccessToken(GraphEnum::CLIENT_ADMIN->value);

        $response = $this->http->request('PATCH', "https://graph.microsoft.com/v1.0/me/events/{$eventId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
                'Prefer' => 'outlook.timezone="Europe/Warsaw"'
            ],
            'json' => $payload,
        ]);

        return $response->toArray(false);
    }

    /**
     * Usuwa wydarzenie z kalendarza Microsoft Graph.
     *
     * Metoda wysyła żądanie DELETE do API Microsoft Graph
     * w celu trwałego usunięcia wskazanego wydarzenia.
     *
     * @param string $eventId Identyfikator wydarzenia w Microsoft Graph
     *
     * @return void
     *
     * @throws TransportExceptionInterface Błąd transportu HTTP
     */
    public function deleteEvent(string $eventId): void
    {
        $token = $this->graphTokenManager->getValidAccessToken(GraphEnum::CLIENT_ADMIN->value);

        $this->http->request('DELETE', "https://graph.microsoft.com/v1.0/me/events/{$eventId}", [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
            ],
        ]);
    }

    /**
     * Buduje payload wydarzenia Microsoft Graph dla spotkania MS Teams.
     *
     * Metoda przygotowuje strukturę danych wymaganą przez Microsoft Graph
     * do utworzenia spotkania online (Teams), w tym listę uczestników,
     * zakres czasu oraz ustawienia spotkania.
     *
     * @param string $subject Tytuł spotkania
     * @param User|string|array $attendees Uczestnicy spotkania (User, e-mail lub lista)
     * @param \DateTimeImmutable $start Data i godzina rozpoczęcia
     * @param \DateTimeImmutable $end Data i godzina zakończenia
     *
     * @return array Payload zgodny z API Microsoft Graph
     */
    public function meetingInfo(Item|string $subject, Student|User|string|array $attendees, \DateTimeImmutable $start, \DateTimeImmutable $end): array
    {
        $fmt = 'Y-m-d\TH:i:s';

        if ($subject instanceof Item) {
            $meetingTitle = $subject->value;
        } else {
            $meetingTitle = (string)$subject;
        }

        $emails = match (true) {
            is_string($attendees) => [$attendees],
            $attendees instanceof User => [$attendees->email],
            is_array($attendees) => array_map(fn($a) => $a instanceof User ? $a->email : (string)$a, $attendees),
        };

        return [
            'subject' => $meetingTitle ?: 'Spotkanie MS Teams',
            'body' => [
                'contentType' => 'HTML',
                'content' => 'Online meeting request'
            ],
            'start' => [
                'dateTime' => $start->format($fmt),
                'timeZone' => 'Europe/Warsaw'
            ],
            'end' => [
                'dateTime' => $end->format($fmt),
                'timeZone' => 'Europe/Warsaw'
            ],
            'attendees' => array_map(fn($e) => [
                'emailAddress' => ['address' => $e],
                'type' => 'required',
            ], $emails),
            'isOnlineMeeting' => true,
            'allowNewTimeProposals' => true,
            'onlineMeetingProvider' => 'teamsForBusiness'
        ];
    }
}
