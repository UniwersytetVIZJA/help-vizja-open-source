<?php

namespace App\Controller\Admin;

use App\Database\Entity\User;
use App\Database\Repository\RegistrationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function array_map;
use function method_exists;

class CalendarController extends AbstractController
{
    public function __construct(private readonly RegistrationRepository $registrationRepository, private readonly UrlGeneratorInterface $generator,

    ) {}

    /**
     * @return Response
     */
    #[Route('/admin/kalendarz', name: 'admin_calendar')]
    #[IsGranted('ROLE_SPECIALIST')]
    public function index(): Response
    {
        return $this->render('admin/calendar/calendar.html.twig');
    }

    /**
     * @return JsonResponse
     */
    #[Route('admin/api/calendar/events', name: 'admin_api_calendar_events')]
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

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Zalogowany użytkownik nie jest pracownikiem');
        }
        $events = $this->registrationRepository->findSpecialistForCalendar($start, $end, $user);

        $data = array_map(function ($e) {
            $title = method_exists($e, 'getTitle') ? $e->getTitle() : ($e->title->value ?? '');
            $startAt = method_exists($e, 'getStartAt') ? $e->getStartAt() : ($e->startsAt ?? null);
            $endAt = method_exists($e, 'getEndAt') ? $e->getEndAt() : ($e->endsAt ?? null);
            $url = method_exists($e, 'getUrl') ? $e->getUrl() : null;
            if (!$url) {
                $id = method_exists($e, 'getId') ? $e->getId() : null;
                $url = $id
                    ? $this->generator->generate('admin_read_registration', ['registrationId' => $id])
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
}
