<?php

namespace App\Controller\Admin;

use App\Core\DictionaryItem\DictionaryItemRepository;
use App\Core\RegisteredStudent\RegisteredStudentManager;
use App\Core\Registration\RegistrationManager;
use App\Core\Student\StudentRepository;
use App\Core\User\UserRepository;
use App\Database\Entity\OfficeRegistration;
use App\Database\Entity\OfficeRegistrationRegisteredStudent;
use App\Database\Entity\RegisteredStudent;
use App\Database\Entity\Registration;
use App\Database\Repository\RegistrationRepository;
use App\Enum\Dictionary\DictionaryNameEnum;
use App\Form\OfficeRegistration\AddStudentForm;
use App\Form\Registration\AddRegistrationForm;
use App\Form\Registration\SignUpRegistrationForm;
use App\Form\Registration\UpdateRegistrationForm;
use App\Graph\GraphCalendarService;
use App\Mailer\Mail\Registration\RemoveStudent;
use App\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function dump;

class RegistrationAdminController extends AbstractController
{
    /**
     * @param RegistrationRepository $registrationRepository
     * @param RegistrationManager $registrationManager
     * @param GraphCalendarService $graphCalendar
     * @param SignUpRegistrationForm $addRegistrationForm
     * @param GraphCalendarService $graph
     * @param StudentRepository $studentRepository
     * @param MailerService $mailerService
     * @param PaginatorInterface $paginator
     * @param HttpClientInterface $httpClient
     */
    public function __construct(
        private readonly RegistrationRepository $registrationRepository,
        private readonly RegistrationManager $registrationManager,
        private readonly GraphCalendarService $graphCalendar,
        private readonly SignUpRegistrationForm $addRegistrationForm,
        private readonly GraphCalendarService $graph, private readonly StudentRepository $studentRepository,
        private readonly MailerService $mailerService, private readonly PaginatorInterface $paginator, private readonly HttpClientInterface $httpClient, private readonly DictionaryItemRepository $dictionaryItemRepository, private readonly UserRepository $userRepository,
        private readonly \App\Database\Repository\UserRepository $userRepo, private readonly TranslatorInterface $translator, private readonly EntityManagerInterface $entityManager, private readonly RegisteredStudentManager $registeredStudentManager
    ) {}

    /**
     * Wyświetla listę zapisów w panelu administracyjnym.
     *
     * Metoda prezentuje aktywne zapisy lub – w przypadku użytkownika
     * z rolą specjalisty – zapisy przypisane do danego specjalisty.
     * Wyniki są wyświetlane z paginacją.
     *
     * @param Request $request Żądanie HTTP zawierające numer strony
     *
     * @return Response Widok listy zapisów z paginacją
     */
    #[Route('/admin/zapisy', name: 'admin_registration')]
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        $titleId = $request->query->get('title');
        $specialistId = $request->query->get('specialist');
        $languageId = $request->query->get('language');

        $fromParam = $request->query->get('from')
            ? new \DateTimeImmutable($request->query->get('from'))
            : null;

        $toParam = $request->query->get('to')
            ? new \DateTimeImmutable($request->query->get('to') . ' 23:59:59')
            : null;

        $title = $titleId ? $this->dictionaryItemRepository->find($titleId) : null;
        $specialist = $specialistId ? $this->userRepository->find($specialistId) : null;
        $language = $languageId ? $this->dictionaryItemRepository->find($languageId) : null;

        $titlesQb = $this->dictionaryItemRepository->findAllByDictionaryNameQueryBuilder(
            DictionaryNameEnum::RODZAJ_KONSULTACJI
        );
        $titles = $titlesQb->getQuery()->getResult();

        $languageQb = $this->dictionaryItemRepository->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::JEZYKI);
        $languages = $languageQb->getQuery()->getResult();

        $specialistsQb = $this->userRepo->findByRole('ROLE_SPECIALIST');
        $specialists = $specialistsQb->getQuery()->getResult();

        $hasFilters = $titleId || $languageId || $specialistId || $fromParam || $toParam;

        if ($this->isGranted('ROLE_EMPLOYEE')) {
            $registrations = $hasFilters
                ? $this->registrationRepository->findFilter($title, $language, $specialist, $fromParam, $toParam)
                : $this->registrationRepository->findActive();
        } else if ($this->isGranted('ROLE_SPECIALIST')) {
            $registrations = $hasFilters
                ? $this->registrationRepository->findFilterBySpecialist(
                    $user->getUserIdentifier(),
                    $title,
                    $language,
                    $specialist,
                    $fromParam,
                    $toParam
                )
                : $this->registrationRepository->findBySpecialist($user->getUserIdentifier());
        } else {
            $registrations = [];
        }

        $studentsCount = 0;
        foreach($registrations as $registration){
            $studentsCount += $registration->registeredStudents->count();
        }

        return $this->render('admin/registration/index.html.twig', [
            'registration' => $registrations,
            'titles' => $titles,
            'languages' => $languages,
            'specialists' => $specialists,
            'studentsCount' => $studentsCount,
            'filters' => [
                'title' => $titleId,
                'language' => $languageId,
                'specialist' => $specialistId,
                'from' => $request->query->get('from'),
                'to' => $request->query->get('to'),
            ],
        ]);
    }

    /**
     * Wyświetla archiwalne zapisy w panelu administracyjnym.
     *
     * Metoda prezentuje zapisy archiwalne lub – w przypadku użytkownika
     * z rolą specjalisty – zapisy przypisane do danego specjalisty.
     * Wyniki są wyświetlane z paginacją.
     *
     * @param Request $request Żądanie HTTP zawierające numer strony
     *
     * @return Response Widok archiwum zapisów z paginacją
     */
    #[Route('/admin/zapisy/archiwum', name: 'admin_registration_archive')]
    public function archive(Request $request): Response
    {
        $user = $this->getUser();

        if ($user->getRoles() == 'ROLE_SPECIALIST') {
            $registration = $this->registrationRepository->findBySpecialist($user->getUserIdentifier());
        } else {
            $registration = $this->registrationRepository->findArchive();
        }

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($registration, $page, 30);

        return $this->render('admin/registration/archive.html.twig', [
            'registration' => $registration,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Wyświetla formularz tworzenia zapisu oraz obsługuje jego zapis.
     *
     * Metoda tworzy nowy zapis na spotkanie, weryfikuje poprawność zakresu dat/godzin
     * oraz zakłada wydarzenie (MS Teams) przez integrację z Microsoft Graph.
     * Po powodzeniu zapis zostaje uzupełniony o link do dołączenia i identyfikator eventu.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza lub przekierowanie po utworzeniu zapisu
     */
    #[Route('/admin/utworz-zapis', name: 'admin_create_registration')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function create(Request $request): Response
    {
        $form = $this->createForm(AddRegistrationForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $baseRegistration = $form->getData();

                $subject = $form->get('title')->getData() ?? 'Spotkanie MS Teams';
                $start = $form->get('startsAt')->getData();
                $end = $form->get('endsAt')->getData();
                $specialist = $form->get('specialist')->getData();
                $language = $form->get('language')->getData();
                $recurrence = $form->get('recurrence')->getData();

                if ($start >= $end) {
                    $this->addFlash('error', $this->translator->trans('Data zakończenia musi być późniejsza niż data rozpoczęcia.'));

                    return $this->render('admin/registration/add-registration.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }

                $interval = match ($recurrence) {
                    'weekly' => '+1 week',
                    '2weeks' => '+2 weeks',
                    'monthly' => '+1 month',
                    default => '+1 day',
                };

                $slotsCount = 0;

                for ($day = $start; $day <= $end; $day = $day->modify($interval)) {
                    $slotsCount++;
                }
                if ($slotsCount > 10) {
                    $this->addFlash('error', $this->translator->trans('W jednym cyklu można utworzyć maksymalnie 10 spotkań Teams (maksymalnie 10 dni w cyklu)'));
                    return $this->redirectToRoute('admin_create_registration');
                }

                $created = 0;

                for ($day = $start; $day <= $end; $day = $day->modify($interval)) {
                    $slotStart = new \DateTimeImmutable(
                        $day->format('Y-m-d') . ' ' . $start->format('H:i')
                    );

                    $slotEnd = new \DateTimeImmutable(
                        $day->format('Y-m-d') . ' ' . $end->format('H:i')
                    );

                    $payload = $this->graphCalendar->meetingInfo(
                        $subject,
                        $specialist,
                        $slotStart,
                        $slotEnd
                    );

                    $event = $this->graph->createEvent($payload);

                    $joinUrl = $event['onlineMeeting']['joinUrl']
                        ?? ($event['joinWebUrl'] ?? null);

                    $eventId = $event['id'] ?? null;

                    if (!$joinUrl || !$eventId) {
                        throw new \RuntimeException('Nie udało się utworzyć spotkania MS Teams.');
                    }

                    $registration = new Registration();

                    $registration->language = $language;
                    $registration->specialist = $specialist;
                    $registration->title = $subject;
                    $registration->capacity = $baseRegistration->capacity ?? 1;
                    $registration->description = $baseRegistration->description ?? null;
                    $registration->startsAt = $slotStart;
                    $registration->endsAt = $slotEnd;

                    $this->registrationManager->create($registration);

                    $this->registrationManager->updateTeams(
                        $registration,
                        $joinUrl,
                        $eventId
                    );

                    $created++;
                }

                $this->addFlash('success', $this->translator->trans('Utworzono terminy: ') . $created);

                return $this->redirectToRoute('admin_registration');
            } catch (\Throwable $e) {
                $this->addFlash('error', $this->translator->trans('Nie udało się utworzyć spotkania: ') . $e->getMessage());
            }
        }

        return $this->render('admin/registration/add-registration.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Wyświetla szczegóły wybranego zapisu.
     *
     * @param string $registrationId Identyfikator zapisu
     *
     * @return Response Widok szczegółów zapisu
     */
    #[Route('/admin/zapisy/szczegoly/{registrationId}', name: 'admin_read_registration')]
    public function read(string $registrationId, Request $request): Response
    {
        $registration = $this->registrationRepository->findOneById($registrationId);

        if (!$registration) {
            throw $this->createNotFoundException('Zapis nie istnieje');
        }

        return $this->render('admin/registration/details-registration.html.twig', [
            'registration' => $registration,
        ]);
    }

    /**
     * Usuwa zapis oraz powiązane wydarzenie kalendarza (MS Teams), jeśli istnieje.
     *
     * Metoda usuwa zapis z systemu, a w przypadku powiązanego wydarzenia
     * próbuje usunąć je z kalendarza. Błąd 404 podczas usuwania wydarzenia
     * jest ignorowany.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param string $registrationId Identyfikator zapisu
     * @param Request $request Żądanie HTTP
     *
     * @return Response Przekierowanie po usunięciu zapisu
     *
     * @throws \Throwable Gdy wystąpi nieobsługiwany błąd podczas usuwania wydarzenia
     */
    #[Route('/admin/zapisy/usun/{registrationId}', name: 'admin_delete_registration')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function deleteRegistration(string $registrationId, Request $request): Response
    {
        $registration = $this->registrationRepository->findOneById($registrationId);

        $eventId = $registration->eventId;

        if ($eventId) {
            try {
                $this->graphCalendar->deleteEvent($eventId);
            } catch (\Throwable $e) {
                $code = method_exists($e, 'getCode') ? (int)$e->getCode() : 0;

                if ($code !== 404) {
                    throw $e;
                }
            }
        }
        $this->registrationManager->deleteRegistration($registration);

        if (!$registration) {
            throw $this->createNotFoundException('Zapis nie istnieje');
        }

        return $this->redirectToRoute('admin_registration');
    }

    /**
     * Wyświetla formularz edycji zapisu oraz obsługuje aktualizację danych.
     *
     * Metoda aktualizuje zapis w systemie oraz – jeśli istnieje powiązane wydarzenie –
     * aktualizuje je w kalendarzu (Microsoft Graph / Teams).
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param string $registrationId Identyfikator edytowanego zapisu
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza edycji lub przekierowanie po zapisie
     *
     * @throws \Throwable Gdy wystąpi błąd podczas aktualizacji wydarzenia w kalendarzu
     */
    #[Route('/admin/zapisy/edytuj/{registrationId}', name: 'admin_update_registration')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateRegistration(string $registrationId, Request $request): Response
    {
        $registration = $this->registrationRepository->find($registrationId);
        if (!$registration) {
            throw $this->createNotFoundException(sprintf('Nie znaleziono zapisu ID: %s', $registrationId));
        }

        $eventId = $registration->eventId;

        $form = $this->createForm(UpdateRegistrationForm::class, $registration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $subject = $form->get('title')->getData();
            $start = $form->get('startsAt')->getData();
            $end = $form->get('endsAt')->getData();
            $specjalist = $form->get('specialist')->getData();

            if ($start->format('d.m.Y') !== $end->format('d.m.Y')) {
                $this->addFlash('error', $this->translator->trans('Nie udało się utworzyć spotkania: '));

                return $this->render('admin/registration/update.html.twig', [
                    'form' => $form,
                ]);
            }

            if ($start->format('H:i') > $end->format('H:i')) {
                $this->addFlash('error', $this->translator->trans('Nie udało się utworzyć zapisu: data zakończenia nie może być mniejsza niż data rozpoczęcia'));

                return $this->render('admin/registration/update.html.twig', [
                    'form' => $form,
                ]);
            }
            $this->registrationManager->updateRegistration($registration, $request);
            $payload = $this->graphCalendar->meetingInfo($subject, $specjalist, $start, $end);

            $this->graph->updateEvent($eventId, $payload);
            
            $this->addFlash('success', $this->translator->trans('Zaktualizowano termin konsultacji'));
        }

        return $this->render('admin/registration/update.html.twig', [
            'registration' => $registration,
            'form' => $form->createView(),
            'errors' => $form->isSubmitted() && !$form->isValid()
        ]);
    }

    /**
     * Usuwa studenta z listy zapisanych na wybrany termin.
     *
     * Metoda anuluje zapis studenta na spotkanie oraz wysyła do niego
     * powiadomienie e-mail o usunięciu z zapisu.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Request $request Żądanie HTTP
     * @param string $registrationId Identyfikator zapisu (terminu)
     * @param string $studentId Identyfikator studenta
     *
     * @return Response Przekierowanie do szczegółów zapisu
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route('/admin/zapisy/szczegoly/{registrationId}/usun-osobe/{studentId}', name: 'admin_delete_student')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function deleteStudent(Request $request, string $registrationId, string $studentId): Response
    {
        $registration = $this->registrationRepository->findOneById($registrationId);
        $student = $this->studentRepository->getOneById($studentId);

        $this->registrationManager->cancelStudentRegistration($student, $registration);

        $mailContent = RemoveStudent::fromEntity($student, $registration);
        $this->mailerService->sendEmailToStudent($student, $mailContent);

        return $this->redirectToRoute('admin_read_registration', [
            'registrationId' => $registration->getId(),
            'studentId' => $student->getUserIdentifier(),
        ]);
    }

    #[Route('/admin/zapisy/{registrationId}/dodaj-studenta', name: 'admin_registration_add_student')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function addStudent(Request $request, Registration $registrationId): Response
    {
        $form = $this->createForm(\App\Form\Registration\AddStudentForm::class)->handleRequest($request);

        if($form->isSubmitted() && $form->isValid()){
            $student = $form->get('registeredStudents')->getData();
            if ($student) {
                $student = $form->get('registeredStudents')->getData();
                $registrationStudent = new RegisteredStudent();
                $registrationStudent->registration = $registrationId;
                $registrationStudent->student = $student;
                $registrationId->registeredStudents->add($registrationStudent);

                $this->entityManager->persist($registrationStudent);
                $this->entityManager->flush();
                $this->addFlash('success', 'Student został dodany do terminu');

                return $this->redirectToRoute('admin_read_registration', [
                    'registrationId' => $registrationId->id,
                ]);

            }
        }

        return $this->render('admin/registration/add-student.html.twig', [
            'form' => $form->createView(),
            'registration' => $registrationId,
        ]);
    }

    #[Route('/admin/zapisy/{registeredStudent}/edytuj-studenta', name: 'admin_registration_update_student')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateStudent(Request $request, RegisteredStudent $registeredStudent): Response
    {
        $form = $this->createForm(\App\Form\Registration\UpdateStudentForm::class, $registeredStudent)->handleRequest($request);

        $registration = $registeredStudent->registration->id;

        if($form->isSubmitted() && $form->isValid()){
           $data = $form->getData();

           $this->registeredStudentManager->update($data);

           return $this->redirectToRoute('admin_read_registration', [
               'registrationId' => $registeredStudent->registration->id,
           ]);
        }

        return $this->render('admin/registration/update-student.html.twig', [
            'form' => $form->createView(),
            'registeredStudent' => $registeredStudent,
            'registrationId' => $registration,
        ]);
    }
}
