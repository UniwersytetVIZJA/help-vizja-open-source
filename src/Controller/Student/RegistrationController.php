<?php

namespace App\Controller\Student;

use App\Core\DictionaryItem\DictionaryItemRepository;
use App\Core\Registration\RegistrationManager;
use App\Core\Student\StudentRepository;
use App\Core\User\UserRepository;
use App\Database\Entity\RegisteredStudent;
use App\Database\Entity\Registration;
use App\Database\Repository\RegisteredStudentRepository;
use App\Database\Repository\RegistrationRepository;
use App\Enum\Dictionary\DictionaryNameEnum;
use App\Form\Registration\SelectRegistrationTypeForm;
use App\Form\Registration\SignUpRegistrationForm;
use App\Graph\GraphCalendarService;
use App\Mailer\Mail\Registration\StudentRegistration;
use App\Mailer\MailerService;
use App\Security\Voter\RegistrationDetailsVoter;
use App\Service\CapacityService;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function dump;
use function preg_match;

class RegistrationController extends AbstractController
{
    /**
     * @param RegistrationRepository $registrationRepository
     * @param RegistrationManager $registrationManager
     * @param GraphCalendarService $graphCalendar
     * @param SignUpRegistrationForm $addRegistrationForm
     * @param GraphCalendarService $graph
     * @param StudentRepository $studentRepository
     * @param MailerService $mailerService
     * @param CapacityService $capacityService
     * @param PaginatorInterface $paginator
     */
    public function __construct(
        private readonly RegistrationRepository $registrationRepository,
        private readonly RegistrationManager $registrationManager,
        private readonly GraphCalendarService $graphCalendar,
        private readonly SignUpRegistrationForm $addRegistrationForm,
        private readonly GraphCalendarService $graph, private readonly StudentRepository $studentRepository, private readonly MailerService $mailerService, private readonly CapacityService $capacityService, private readonly PaginatorInterface $paginator, private readonly DictionaryItemRepository $dictionaryItemRepository, private readonly UserRepository $userRepository,
        private readonly \App\Database\Repository\UserRepository $userRepo, private readonly RegisteredStudentRepository $registeredStudentRepository,
        private readonly TranslatorInterface $translator, private readonly EntityManagerInterface $entityManager
    ) {}

    #[Route('/zapisy/konsultacje', name: 'student_registration_menu')]
    public function consultation(): Response
    {
        return $this->render('student/registration/menu.html.twig');
    }

    /**
     * Wyświetla listę dostępnych zapisów dla studenta.
     *
     * Metoda prezentuje aktywne zapisy wraz z informacją
     * o dostępności miejsc oraz możliwością dostępu do szczegółów.
     *
     * @param Request $request Żądanie HTTP zawierające numer strony
     *
     * @return Response Widok listy zapisów z paginacją
     */
    #[Route('/zapisy', name: 'student_registration')]
    public function index(Request $request): Response
    {
        $titleId = $request->query->get('title');
        $specialistId = $request->query->get('specialist');
        $languageId = $request->query->get('language');

        $from = $request->query->get('from');
        $to = $request->query->get('to');

        if ($from && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
            throw new \InvalidArgumentException('Nieprawidłowy format daty "od".');
        }

        if ($to && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
            throw new \InvalidArgumentException('Nieprawidłowy format daty "do".');
        }

        $fromParam = $from
            ? new \DateTimeImmutable($from)
            : null;

        $toParam = $to
            ? new \DateTimeImmutable($to . ' 23:59:59')
            : null;

        $title = $titleId ? $this->dictionaryItemRepository->find($titleId) : null;
        $specialist = $specialistId ? $this->userRepository->find($specialistId) : null;
        $language = $languageId ? $this->dictionaryItemRepository->find($languageId) : null;

        $hasFilters = $titleId || $languageId || $specialistId || $fromParam || $toParam;

        $registrations = $hasFilters
            ? $this->registrationRepository->findFilter($title, $language, $specialist, $fromParam, $toParam)
            : $this->registrationRepository->findActive();

        $now = new \DateTimeImmutable();
        $canSign = [];

        foreach ($registrations as $registration) {
            $term = $registration->startsAt;
            $allowed = true;

            if ($term instanceof \DateTimeInterface) {
                $deadline = \DateTimeImmutable::createFromInterface($term)->modify('-12 hours');
                $allowed = $now < $deadline;
            }

            $canSign[$registration->id] = $allowed;
        }

        $capacities = [];
        $canAccess = [];

        foreach ($registrations as $reg) {
            $capacities[$reg->getId()] = $this->capacityService->isFull($reg);
            $canAccess[$reg->id] = $this->registrationRepository->canAccess($reg, $this->getUser());
        }

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($registrations, $page);

        return $this->render('student/registration/index.html.twig', [
            'registration' => $registrations,
            'canSign' => $canSign,
            'capacities' => $capacities,
            'canAccess' => $canAccess,
            'pagination' => $pagination,
            'filters' => [
                'title' => $title ? $title->value : null,
                'language' => $language ? $language->value : null,
                'specialist' => $specialist
                    ? $specialist->firstName . ' ' . $specialist->lastName
                    : null,
                'from' => $fromParam,
                'to' => $toParam,
            ],
        ]);
    }

    #[Route('/zapisy/szukaj', name: 'student_select_registration')]
    public function selectRegistration(Request $request): Response
    {
        $titles = $this->dictionaryItemRepository
            ->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::RODZAJ_KONSULTACJI)
            ->getQuery()
            ->getResult();

        $languages = $this->dictionaryItemRepository
            ->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::JEZYKI)
            ->getQuery()
            ->getResult();

        $specialistsQb = $this->userRepo->findByRole('ROLE_SPECIALIST');
        $specialists = $specialistsQb->getQuery()->getResult();

        return $this->render('student/registration/select-registration.html.twig', [
            'specialists' => $specialists,
            'titles' => $titles,
            'languages' => $languages,
            'filters' => [
                'title' => null,
                'language' => null,
                'specialist' => null,
                'from' => null,
                'to' => null,
            ],
        ]);
    }

    #[Route('/zapisy/moje-zapisy', name: 'student_my_registrations')]
    public function myRegistrations(Request $request): Response
    {
        $user = $this->getUser();

        $registrations = $this->registeredStudentRepository->findUpcoming($user);

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($registrations, $page);

        return $this->render('student/registration/my-registrations.html.twig', [
            'registration' => $registrations,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/zapisy/moje-zapisy/archiwum', name: 'student_my_registrations_archive')]
    public function myRegistrationsArchive(Request $request): Response
    {
        $user = $this->getUser();

        $registrations = $this->registeredStudentRepository->findArchive($user);

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($registrations, $page);

        return $this->render('student/registration/my-registrations-archive.html.twig', [
            'registration' => $registrations,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Wyświetla szczegóły zapisu dla studenta.
     *
     * Metoda sprawdza uprawnienia dostępu do zapisu
     * i prezentuje jego szczegóły.
     *
     * @param string $registrationId Identyfikator zapisu
     *
     * @return Response Widok szczegółów zapisu
     */
    #[Route('/zapisy/szczegoly/{registrationId}', name: 'student_read_registration')]
    public function read(string $registrationId): Response
    {
        $registration = $this->registrationRepository->findOneById($registrationId);

        if (!$registration) {
            throw $this->createNotFoundException('Zapis nie istnieje');
        }

        $this->denyAccessUnlessGranted(RegistrationDetailsVoter::ACCESS, $registration);

        return $this->render('student/registration/registration-details.html.twig', [
            'registration' => $registration,
        ]);
    }

    /**
     * Wyświetla formularz zapisu na wybrany termin oraz obsługuje jego wysłanie.
     *
     * Metoda sprawdza dostępność miejsc, a następnie umożliwia studentowi zapis
     * na konsultacje. Po poprawnym zapisie wysyłane jest potwierdzenie e-mail
     * i następuje przekierowanie do listy zapisów.
     *
     * @param string $registrationId Identyfikator terminu zapisów
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza zapisu lub przekierowanie po zapisie
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route('/zapisy/formularz/{registrationId}', name: 'student_signup_registration', methods: ['GET', 'POST'])]
    public function signUp(string $registrationId, Request $request): Response
    {
        $registration = $this->registrationRepository->findOneById($registrationId);

        if (!$registration) {
            throw $this->createNotFoundException('Zapis nie istnieje');
        }

        $now = new \DateTimeImmutable();

        if ($registration->startsAt instanceof \DateTimeImmutable) {
            $deadline = $registration->startsAt->modify('-12 hours');

            if ($now >= $deadline) {
                $this->addFlash('error', $this->translator->trans('Termin zapisu na konsultację już minął'));

                return $this->redirectToRoute('student_registration');
            }
        }

        $student = $this->getUser();

        $registrationEntity = new RegisteredStudent();

        $this->registrationRepository->getInfoFromStudent($student, $registrationEntity);

        $form = $this->createForm(SignUpRegistrationForm::class, $registrationEntity);

        if ($registration instanceof Registration && $registration->language->value !== 'Polski i Angielski') {
            $form->remove('language');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $data = $form->getData();

                $this->entityManager->wrapInTransaction(function () use ($registrationId, $student, $data) {
                    $lockedRegistration = $this->entityManager->find(
                        Registration::class,
                        $registrationId,
                        LockMode::PESSIMISTIC_WRITE
                    );

                    if (!$lockedRegistration) {
                        throw new \RuntimeException('Zapis nie istnieje.');
                    }

                    $capacity = $lockedRegistration->capacity;
                    $taken = $lockedRegistration->registeredStudents->count();

                    if ($capacity !== null && $taken >= $capacity) {
                        throw new \RuntimeException('Brak wolnych miejsc na ten termin.');
                    }

                    $this->registrationManager->createStudentRegistration(
                        $student,
                        $lockedRegistration,
                        $data
                    );

                    $this->registrationManager->updateStudentInfo(
                        $student,
                        $data
                    );
                });

                $registration = $this->registrationRepository->findOneById($registrationId);

                $mailContent = StudentRegistration::fromEntity($student, $registration, $registrationEntity);
                $this->mailerService->sendEmailToStudent($student, $mailContent);

                $this->addFlash(
                    'success',
                    $this->translator->trans('Zapisano na konsultacje. Szczegółowe informacje znajdują się poniżej.')
                );

                return $this->redirectToRoute('student_read_registration', [
                    'registrationId' => $registrationId,
                ]);
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
                $this->addFlash('error', $this->translator->trans('Jesteś już zapisany/a na te konsultacje.'));
            } catch (\RuntimeException $e) {
                $this->addFlash('danger', $this->translator->trans($e->getMessage()));
            }
        }

        return $this->render('student/registration/registration-sign-up.html.twig', [
            'registration' => $registration,
            'registrationId' => $registrationId,
            'form' => $form,
        ]);
    }

}
