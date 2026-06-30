<?php

namespace App\Controller\Student;

use App\Database\Entity\Student;
use App\Database\Repository\OfficeRegistrationRepository;
use App\Database\Repository\RegisteredStudentRepository;
use App\Database\Repository\RegistrationRepository;
use DateMalformedStringException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use function array_map;
use function method_exists;

class CalendarController extends AbstractController
{
    /**
     * @param RegistrationRepository $meetingRepository
     * @param EntityManagerInterface $entityManager
     * @param SecurityController $securityController
     * @param RegistrationRepository $registrationRepository
     * @param RegisteredStudentRepository $registeredStudentRepository
     */
    public function __construct(
        private readonly RegistrationRepository $meetingRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SecurityController $securityController,
        private readonly RegistrationRepository $registrationRepository, private readonly RegisteredStudentRepository $registeredStudentRepository,
        private readonly UrlGeneratorInterface $generator, private readonly OfficeRegistrationRepository $officeRegistrationRepository
    ) {}

    /**
     * Wyświetla kalendarz studenta.
     *
     * @return Response Widok kalendarza
     */
    #[Route('/kalendarz', name: 'student_calendar_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('student/calendar/index.html.twig');
    }

    /**
     * Zwraca wydarzenia kalendarza studenta w zadanym zakresie dat.
     *
     * Endpoint API pobiera zakres dat (`start`, `end`) z parametrów zapytania
     * i zwraca listę wydarzeń studenta w formacie JSON,
     * przeznaczoną do wyświetlenia w kalendarzu.
     *
     * @param Request $request Żądanie HTTP zawierające opcjonalne parametry `start` i `end`
     *
     * @return JsonResponse Lista wydarzeń kalendarza
     *
     * @throws DateMalformedStringException Gdy przekazano nieprawidłowy format daty
     * @throws \LogicException Gdy zalogowany użytkownik nie jest studentem
     */
    #[Route('kalendarz/api/calendar/events', name: 'student_api_calendar_events', methods: ['GET'])]
    public function events(Request $request): JsonResponse
    {
        $startStr = (string)$request->query->get('start', '');
        $endStr = (string)$request->query->get('end', '');

        try {
            $start = $startStr
                ? new \DateTimeImmutable($startStr)
                : new \DateTimeImmutable('first day of this month 00:00:00');

            $end = $endStr
                ? new \DateTimeImmutable($endStr)
                : new \DateTimeImmutable('last day of this month 23:59:59');
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Nieprawidłowy format daty.');
        }

        $student = $this->getUser();
        if (!$student instanceof Student) {
            throw new \LogicException('Zalogowany użytkownik nie jest studentem');
        }
        $events = $this->registrationRepository->findForCalendar($start, $end, $student);

        $data = array_map(function ($e) {
            $title = method_exists($e, 'getTitle') ? $e->getTitle() : ($e->title->value ?? '');
            $startAt = method_exists($e, 'getStartAt') ? $e->getStartAt() : ($e->startsAt ?? null);
            $endAt = method_exists($e, 'getEndAt') ? $e->getEndAt() : ($e->endsAt ?? null);
            $url = method_exists($e, 'getUrl') ? $e->getUrl() : null;
            if (!$url) {
                $id = method_exists($e, 'getId') ? $e->getId() : null;
                $url = $id
                    ? $this->generator->generate('student_read_registration', ['registrationId' => $id])
                    : null;
            }
            $specialist = method_exists($e, 'getSpecialist') ? $e->getSpecialist() : ($e->specialist ?? null);

            return [
                'id' => method_exists($e, 'getId') ? $e->getId() : null,
                'title' => $title ?: '(bez tytułu)',
                'start' => $startAt?->format(\DateTimeInterface::ATOM),
                'end' => $endAt?->format(\DateTimeInterface::ATOM),
                'allDay' => false,
                'url' => $url ?: null,
                'extendedProps' => [
                    'specialistName' => $specialist,
                    'meetingMode' => 'Online',
                ]
            ];
        }, $events);

        return $this->json($data);
    }

    #[Route('kalendarz/api/calendar/events/bon', name: 'student_api_calendar_events_bon', methods: ['GET'])]
    public function eventsBon(Request $request): JsonResponse
    {
        $startStr = (string)$request->query->get('start', '');
        $endStr = (string)$request->query->get('end', '');

        try {
            $start = $startStr
                ? new \DateTimeImmutable($startStr)
                : new \DateTimeImmutable('first day of this month 00:00:00');

            $end = $endStr
                ? new \DateTimeImmutable($endStr)
                : new \DateTimeImmutable('last day of this month 23:59:59');
        } catch (\Exception $e) {
            throw new BadRequestHttpException('Nieprawidłowy format daty.');
        }

        $student = $this->getUser();
        if (!$student instanceof Student) {
            throw new \LogicException('Zalogowany użytkownik nie jest studentem');
        }
        $events = $this->officeRegistrationRepository->findForCalendar($start, $end, $student);

        $data = array_map(function ($e) {
            $startAt = $e->startAt;
            $endAt = $e->endAt;

            return [
                'id' => $e->id,
                'title' => 'Wizyta w biurze BON',
                'start' => $startAt->format(\DateTimeInterface::ATOM),
                'end' => $endAt->format(\DateTimeInterface::ATOM),
                'allDay' => false,
                'extendedProps' => [
                    'source' => 'bon',
                ],
            ];
        }, $events);

        return $this->json($data);
    }
}
