<?php

namespace App\Controller\Admin;

use App\Core\OfficeRegistrationManager\OfficeRegistrationManager;
use App\Database\Entity\OfficeRegistration;
use App\Database\Entity\OfficeRegistrationRegisteredStudent;
use App\Database\Repository\OfficeRegistrationRegisteredStudentRepository;
use App\Database\Repository\OfficeRegistrationRepository;
use App\Form\OfficeRegistration\AddStudentForm;
use App\Form\OfficeRegistration\CreateOfficeRegistrationForm;
use App\Form\OfficeRegistration\UpdateOfficeRegistrationForm;
use App\Graph\GraphCalendarService;
use App\Mailer\Mail\OfficeRegistration\OfficeRegistrationAccept;
use App\Mailer\Mail\OfficeRegistration\OfficeRegistrationCancel;
use App\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function array_map;
use function dump;
use function preg_match;
use function strtolower;

class OfficeRegistrationController extends AbstractController
{
    public function __construct(private readonly OfficeRegistrationRepository $officeRegistrationRepository, private readonly EntityManagerInterface $entityManager, private readonly OfficeRegistrationManager $officeRegistrationManager, private readonly UrlGeneratorInterface $generator, private readonly TranslatorInterface $translator, private readonly GraphCalendarService $graphCalendarService, private readonly MailerService $mailerService, private readonly OfficeRegistrationRegisteredStudentRepository $officeRegistrationRegisteredStudentRepository) {}

    #[Route('/admin/zapisy-bon', name: 'admin_office_registration_index')]
    public function index(Request $request): Response
    {
        return $this->render('admin/office-registration/index.html.twig');
    }

    #[Route('/admin/zapisy-bon/wizyty', name: 'admin_office_registration_choose_reg')]
    public function chooseReg(Request $request): Response
    {
        return $this->render('admin/office-registration/choose-reg.html.twig');
    }

    #[Route('/admin/zapisy-bon/aktywne-wizyty/{id}', name: 'admin_office_registration_active_reg')]
    public function activeReg(OfficeRegistration $id): Response
    {
        if(!$id){
            throw $this->createNotFoundException($this->translator->trans('Nie znaleziono zapisu'));
        }

        $registeredStudents = $this->officeRegistrationRegisteredStudentRepository->findActiveMeetings($id->id);


        return $this->render('admin/office-registration/active-reg.html.twig', [
            'registration' => $id,
            'registeredStudents' => $registeredStudents
        ]);
    }

    #[Route('/admin/zapisy-bon/archiwalne-wizyty', name: 'admin_office_registration_archive_reg')]
    public function archiveReg(Request $request): Response
    {
        $registrations = $this->officeRegistrationRepository->findArchiveRegistrationAdmin();

        return $this->render('admin/office-registration/archive-reg.html.twig', [
            'registrations' => $registrations,
        ]);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    #[Route('/admin/zapisy-bon/anuluj-rezerwacje/{id}', name: 'admin_office_registration_cancel')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function cancelTerm(OfficeRegistrationRegisteredStudent $id): Response
    {
        $id->confirmed = false;
        $this->entityManager->flush();
        $student = $id->student;
        $registration = $id->registration;

        $this->addFlash('success', $this->translator->trans('Wizyta anulowana'));

        $mailContent = OfficeRegistrationCancel::fromEntity($student, $registration);
        $this->mailerService->sendEmailToStudent($student, $mailContent);

        return $this->redirectToRoute('admin_office_registration_active_reg', [
            'id' => $registration->id,
        ]);
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    #[Route('/admin/zapisy-bon/potwierdz-rezerwacje/{id}', name: 'admin_office_registration_confirm')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function confirmTerm(Request $request, OfficeRegistrationRegisteredStudent $id): Response
    {
        $id->confirmed = true;
        $this->entityManager->flush();
        $student = $id->student;

        $registration = $id->registration;
        $locale = $request->getLocale();

        $subject = match ($locale) {
            default => 'Wizyta w BON',
            'en' => 'A visit to BON',
        };

        $attendees = [$id->student->email];
        $start = $registration->startAt;
        $end = $registration->endAt;

        try {
            if ($id->meetingMode === 'Spotkanie online') {
                $payload = $this->graphCalendarService->meetingInfo($subject, $attendees, $start, $end);
                $event = $this->graphCalendarService->createEvent($payload);
            }

            $joinURL = $event['onlineMeeting']['joinUrl'] ?? ($event['joinWebUrl'] ?? null);
            $eventId = $event['id'] ?? null;

            if ($joinURL && $eventId) {
                $this->officeRegistrationManager->updateTeams($registration, $joinURL, $eventId);
                $this->addFlash('success', $this->translator->trans('Potwierdzono wizytę z linkiem do spotkania na MS Teams'));
            } else {
                $this->addFlash('success', $this->translator->trans('Wizyta potwierdzona bez spotkania na MS Teams'));
            }
        } catch (\Throwable $e) {
            $this->addFlash('error', $this->translator->trans('Nie udało się utworzyć spotkania: ') . $e->getMessage());
        }

        $mailContent = OfficeRegistrationAccept::fromEntity($student, $registration);
        $this->mailerService->sendEmailToStudent($student, $mailContent);

        return $this->redirectToRoute('admin_office_registration_active_reg', [
            'id' => $registration->id,
        ]);
    }

    #[Route('/admin/zapisy-bon/terminy', name: 'admin_office_registration_terms')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function terms(): Response
    {
        return $this->render('admin/office-registration/terms.html.twig');
    }

    #[Route('/admin/zapisy-bon/terminy/edytuj-termin/{id}', name: 'admin_office_registration_terms_edit')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function editTerm(Request $request, OfficeRegistration $id): Response
    {
        $form = $this->createForm(UpdateOfficeRegistrationForm::class, $id);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->officeRegistrationManager->update($data);

            $this->addFlash('success', $this->translator->trans('Termin wizyty został zaktualizowany'));

            return $this->redirectToRoute('admin_office_registration_terms');
        }

        return $this->render('admin/office-registration/edit-term.html.twig', [
            'form' => $form->createView(),
            'registration' => $id,
        ]);
    }

    #[Route('/admin/zapisy-bon/terminy/usun-termin/{id}', name: 'admin_office_registration_terms_delete', methods: ['POST', 'DELETE'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function deleteTerm(Request $request, OfficeRegistration $id): Response
    {
        if(!$id){
            throw $this->createNotFoundException($this->translator->trans('Nie znaleziono terminu'));
        }

        if($id->registeredStudents->count()){
            $this->addFlash('danger', $this->translator->trans('Nie można usunąć terminu - wykryto zapisanych studentów'));

            return $this->redirectToRoute('admin_office_registration_active_reg', [
                'id' => $id->id,
            ]);
        }


        if(!$this->isCsrfTokenValid('delete_office_term_' . $id->id, $request->request->get('_token'))){
            throw $this->createAccessDeniedException(
                $this->translator->trans('Nie udało się zweryfikować bezpieczeństwa żądania. Odśwież stronę i spróbuj ponownie')
            );
        }

        $this->officeRegistrationManager->delete($id);

        return $this->redirectToRoute('admin_office_registration_terms');
    }

    #[Route('/admin/zapisy-bon/terminy/dodaj-termin', name: 'admin_office_registration_terms_create')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function createTerm(Request $request): Response
    {
        $form = $this->createForm(CreateOfficeRegistrationForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $from = $form->get('from')->getData();
            $to = $form->get('to')->getData();
            $timeFrom = $form->get('timeFrom')->getData();
            $timeTo = $form->get('timeTo')->getData();

            $from = \DateTimeImmutable::createFromInterface($from)->setTime(0, 0);
            $to = \DateTimeImmutable::createFromInterface($to)->setTime(0, 0);

            if ($from > $to) {
                $form->addError(new FormError('Data "od" nie może być później niż "do".'));
            } else if (!$timeFrom || !$timeTo) {
                $form->addError(new FormError('Godzina rozpoczęcia i zakończenia są wymagane.'));
            } else {
                $startHour = (int)$timeFrom->format('H');
                $startMinute = (int)$timeFrom->format('i');

                $endHour = (int)$timeTo->format('H');
                $endMinute = (int)$timeTo->format('i');

                for ($day = $from; $day <= $to; $day = $day->modify('+1 day')) {
                    $startAt = $day->setTime($startHour, $startMinute);
                    $endAt = $day->setTime($endHour, $endMinute);

                    if ($startAt >= $endAt) {
                        $form->addError(new FormError('Godzina rozpoczęcia musi być wcześniejsza niż godzina zakończenia.'));
                        break;
                    }

                    $term = new OfficeRegistration();
                    $term->startAt = $startAt;
                    $term->endAt = $endAt;

                    $this->entityManager->persist($term);
                }

                if (count($form->getErrors(true)) === 0) {
                    $this->entityManager->flush();

                    return $this->redirectToRoute('admin_office_registration_terms');
                }
            }
        }

        return $this->render('admin/office-registration/create-term.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/api/office-terms', name: 'admin_office_terms', methods: ['GET'])]
    public function guestOfficeTerms(Request $request): JsonResponse
    {
        $date = (string) $request->query->get('date');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->json(['slots' => []]);
        }

        try {
            $start = new \DateTimeImmutable($date . ' 00:00:00');
        } catch (\Throwable) {
            return $this->json(['slots' => []]);
        }

        $end = $start->modify('+1 day');

        /** @var OfficeRegistration[] $slots */
        $slots = $this->entityManager->getRepository(OfficeRegistration::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.registeredStudents', 'rs')
            ->addSelect('rs')
            ->andWhere('s.startAt >= :start')
            ->andWhere('s.startAt < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        $out = array_map(static function (OfficeRegistration $s) {
            $status = 0;

            foreach ($s->registeredStudents as $student) {
                if ($student->meetingMode === null) {
                    continue;
                }

                if ($student->confirmed === null) {
                    $status = null;
                }

                if ($student->confirmed === true) {
                    $status = 1;
                    break;
                }
            }

            return [
                'id' => $s->id,
                'time' => $s->startAt->format('H:i') . ' - ' . $s->endAt->format('H:i'),
                'registered' => $status,
            ];
        }, $slots);

        return $this->json(['slots' => $out]);
    }

    #[Route('/admin/api/office-terms/days', name: 'admin_office_terms_days', methods: ['GET'])]
    public function officeTermsDays(Request $request): JsonResponse
    {
        $month = (string) $request->query->get('month');

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $this->json([
                'days' => [],
                'daysAvailable' => [],
                'daysPending' => [],
                'daysConfirmed' => [],
            ]);
        }

        try {
            $start = new \DateTimeImmutable($month . '-01 00:00:00');
        } catch (\Throwable) {
            return $this->json([
                'days' => [],
                'daysAvailable' => [],
                'daysPending' => [],
                'daysConfirmed' => [],
            ]);
        }

        $end = $start->modify('first day of next month');
        $now = new \DateTimeImmutable();

        /** @var OfficeRegistration[] $slots */
        $slots = $this->entityManager->getRepository(OfficeRegistration::class)
            ->createQueryBuilder('s')
            ->leftJoin('s.registeredStudents', 'rs')
            ->addSelect('rs')
            ->andWhere('s.startAt >= :start')
            ->andWhere('s.startAt < :end')
            ->andWhere('s.startAt >= :now')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('now', $now)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        $days = [];
        $daysAvailable = [];
        $daysPending = [];
        $daysConfirmed = [];

        foreach ($slots as $slot) {
            $day = $slot->startAt->format('Y-m-d');

            $hasVisibleRegistration = false;

            foreach ($slot->registeredStudents as $student) {
                if ($student->meetingMode === null) {
                    continue;
                }

                $hasVisibleRegistration = true;

                if ($student->confirmed === null) {
                    $daysPending[$day] = true;
                }

                if ($student->confirmed === true) {
                    $daysConfirmed[$day] = true;
                }
            }

            if ($hasVisibleRegistration) {
                $days[$day] = true;
                continue;
            }

            $days[$day] = true;
            $daysAvailable[$day] = true;
        }

        return $this->json([
            'days' => array_keys($days),
            'daysAvailable' => array_keys($daysAvailable),
            'daysPending' => array_keys($daysPending),
            'daysConfirmed' => array_keys($daysConfirmed),
        ]);
    }

    #[Route('/admin/zapisy-bon/kalendarz', name: 'admin_office_registration_calendar')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function calendar(): Response
    {
        return $this->render('admin/office-registration/calendar.html.twig');
    }

    #[Route('/admin/zapisy-bon/{officeRegistrationId}/dodaj-studenta', name: 'admin_office_registration_add_student')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function addStudent(Request $request, OfficeRegistration $officeRegistrationId): Response
    {
        $form = $this->createForm(AddStudentForm::class)->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $student = $form->get('registeredStudents')->getData();
            if ($student) {
                $student = $form->get('registeredStudents')->getData();
                $registrationStudent = new OfficeRegistrationRegisteredStudent();
                $registrationStudent->registration = $officeRegistrationId;
                $registrationStudent->student = $student;
                $registrationStudent->confirmed = null;
                $officeRegistrationId->registeredStudents->add($registrationStudent);

                $this->entityManager->persist($registrationStudent);

                $this->entityManager->flush();
                $this->addFlash('success', 'Student został dodany do terminu');

                return $this->redirectToRoute('admin_office_registration_active_reg', [
                    'id' => $officeRegistrationId->id,
                    'officeRegistration' => $officeRegistrationId,
                ]);

            }
        }

        return $this->render('admin/office-registration/add-student.html.twig', [
            'form' => $form->createView(),
            'officeRegistration' => $officeRegistrationId,
        ]);
    }

    /**
     * @return JsonResponse
     */
    #[Route('/admin/zapisy-bon/api/calendar/events', name: 'admin_api_calendar_events_office_registration')]
    public function events(Request $request): JsonResponse
    {
        $startStr = (string)$request->query->get('start', '');
        $endStr = (string)$request->query->get('end', '');

        try {
            $start = $startStr !== ''
                ? new \DateTimeImmutable($startStr)
                : new \DateTimeImmutable('first day of this month 00:00:00');

            $end = $endStr !== ''
                ? new \DateTimeImmutable($endStr)
                : new \DateTimeImmutable('last day of this month 23:59:59');
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Nieprawidłowy format daty.',
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $events = $this->officeRegistrationRepository->findRegistrationsforCalendar($start, $end);

        $data = array_map(function (OfficeRegistration $registration) {
            $studentName = null;
            $confirmed = null;
            $meetingMode = null;

            foreach ($registration->registeredStudents as $registeredStudent) {
                $fullName = trim(sprintf(
                    '%s %s',
                    $registeredStudent->student->firstName ?? '',
                    $registeredStudent->student->lastName ?? ''
                ));

                if ($registeredStudent->confirmed === true) {
                    $studentName = $fullName;
                    $confirmed = true;
                    $meetingMode = $registeredStudent->meetingMode;
                    break;
                }

                if ($studentName === null) {
                    $studentName = $fullName !== '' ? $fullName : null;
                    $confirmed = $registeredStudent->confirmed;
                    $meetingMode = $registeredStudent->meetingMode;
                }
            }

            $url = $this->generator->generate('admin_office_registration_active_reg', [
                'id' => $registration->getId(),
            ]);

            return [
                'id' => $registration->getId(),
                'title' => $studentName ?: '(brak studenta)',
                'start' => $registration->startAt?->format(\DateTimeInterface::ATOM),
                'end' => $registration->endAt?->format(\DateTimeInterface::ATOM),
                'color' => match ($confirmed) {
                    true => '#22c55e',
                    false => '#ef4444',
                    null => '#f8b64b',
                },
                'allDay' => false,
                'url' => $url,
                'extendedProps' => [
                    'studentName' => $studentName,
                    'meetingMode' => $meetingMode,
                    'confirmed' => $confirmed,
                ],
            ];
        }, $events);

        return $this->json($data);
    }
}
