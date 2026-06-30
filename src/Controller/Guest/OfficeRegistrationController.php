<?php

namespace App\Controller\Guest;

use App\Core\OfficeRegistrationManager\OfficeRegistrationManager;
use App\Core\Student\StudentManager;
use App\Database\Entity\OfficeRegistration;
use App\Database\Entity\OfficeRegistrationRegisteredStudent;
use App\Database\Repository\OfficeRegistrationRegisteredStudentRepository;
use App\Database\Repository\OfficeRegistrationRepository;
use App\Form\OfficeRegistration\OfficeRegistrationForm;
use App\Form\OfficeRegistration\SignUpOfficeRegistrationForm;
use App\Form\RegisterAccount\CreateAccountForm;
use App\Mailer\Mail\OfficeRegistration\NewOfficeRegistration;
use App\Mailer\MailerService;
use App\Security\LoginFormAuthenticator;
use DateMalformedStringException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Component\Security\Http\Util\TargetPathTrait;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function array_keys;
use function array_map;
use function preg_match;

class OfficeRegistrationController extends AbstractController
{
    use TargetPathTrait;

    public function __construct(private readonly StudentManager $studentManager, private readonly UserAuthenticatorInterface $userAuthenticator, private readonly LoginFormAuthenticator $authenticator,
        private readonly EntityManagerInterface $em, private readonly OfficeRegistrationManager $officeRegistrationManager, private readonly OfficeRegistrationRepository $officeRegistrationRepository, private readonly TranslatorInterface $translator, private readonly OfficeRegistrationRegisteredStudentRepository $officeRegistrationRegisteredStudentRepository,
        #[Autowire(service: 'limiter.guest_create_account')]
        private readonly RateLimiterFactory $rateLimiterFactory, private readonly MailerService $mailerService,
    ) {}

    #[Route('/gosc/zapisy-bon/krok-1', name: 'guest_office_registration_step_1')]
    public function createAccount(Request $request): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('guest_office_registration_step_2');
        }

        $form = $this->createForm(CreateAccountForm::class);

        if ($request->isMethod('POST')) {
            $limiter = $this->rateLimiterFactory->create(
                $request->getClientIp() ?? 'unknown',
            );

            $limit = $limiter->consume(1);

            if (!$limit->isAccepted()) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans('Zbyt wiele prób utworzenia konta. Spróbuj ponownie za kilka minut.'),
                );

                return $this->redirectToRoute('guest_office_registration_step_1');
            }
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $firstName = $form->get('firstName')->getData();
            $lastName = $form->get('lastName')->getData();
            $email = $form->get('email')->getData();
            $password = $form->get('password')->getData();

            $user = $this->studentManager->createGuest(
                $firstName,
                $lastName,
                $email,
                $password,
            );

            $this->saveTargetPath(
                $request->getSession(),
                'main',
                $this->generateUrl('guest_office_registration_step_2'),
            );

            $this->addFlash(
                'success',
                $this->translator->trans('Konto zostało pomyślnie utworzone'),
            );

            return $this->userAuthenticator->authenticateUser(
                $user,
                $this->authenticator,
                $request,
            );
        }

        return $this->render('guest/office-registration/step-1.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/gosc/zapisy-bon/krok-2', name: 'guest_office_registration_step_2')]
    #[IsGranted('ROLE_GOSC')]
    public function chooseDate(Request $request): Response
    {
        $form = $this->createForm(OfficeRegistrationForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $termId = $form->get('termId')->getData();

            if (!$termId) {
                $this->addFlash('danger', $this->translator->trans('Wybierz godzinę wizyty.'));

                return $this->redirectToRoute('guest_office_registration_step_2');
            }

            $term = $this->officeRegistrationRepository->find($termId);
            if (!$term) {
                $this->addFlash('danger', $this->translator->trans('Wybrany termin nie istnieje.'));

                return $this->redirectToRoute('guest_office_registration_step_2');
            }
            $termTime = $term->startAt;
            $now = new \DateTimeImmutable();
            $canBook = true;

            if ($termTime instanceof \DateTimeImmutable) {
                $deadline = \DateTimeImmutable::createFromInterface($termTime)->modify('-12 hours');
                $canBook = $now < $deadline;
            }
            if (!$canBook) {
                $this->addFlash('danger', $this->translator->trans('Termin na rezerwację godziny już minął'));

                return $this->redirectToRoute('guest_office_registration_step_2');
            }

            $slot = $term;
            if (!$slot) {
                $this->addFlash('danger', $this->translator->trans('Wybrany termin nie istnieje.'));

                return $this->redirectToRoute('guest_office_registration_step_2');
            }

            $activeRegistration = $this->officeRegistrationRegisteredStudentRepository->findOneBy([
                'registration' => $slot,
                'confirmed' => true,
            ]);

            if ($activeRegistration) {
                $this->addFlash('danger', $this->translator->trans('Ten termin nie jest dostępny. Wybierz inny.'));
            } else {
                $registrationStudent = new OfficeRegistrationRegisteredStudent();
                $registrationStudent->registration = $slot;
                $registrationStudent->student = $this->getUser();

                $this->em->persist($registrationStudent);
                $this->em->flush();

                return $this->redirectToRoute('guest_office_registration_step_3', [
                    'id' => $slot->id,
                ]);
            }
        }

        return $this->render('guest/office-registration/step-2.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws DateMalformedStringException
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/gosc/zapisy-bon/krok-3/{id}', name: 'guest_office_registration_step_3')]
    #[IsGranted('ROLE_GOSC')]
    public function confirmTerm(Request $request, OfficeRegistration $id): Response
    {
        $user = $this->getUser();

        $registrationStudent = $this->officeRegistrationRegisteredStudentRepository
            ->findPendingForStudent($id, $user);

        if (!$registrationStudent) {
            throw $this->createAccessDeniedException('Brak dostępu do tej wizyty');
        }

        $termTime = $id->startAt;
        $now = new \DateTimeImmutable();

        if ($termTime instanceof \DateTimeInterface) {
            $deadline = \DateTimeImmutable::createFromInterface($termTime)->modify('-12 hours');

            if ($now >= $deadline) {
                $this->addFlash('danger', $this->translator->trans('Termin na rezerwację godziny już minął'));

                return $this->redirectToRoute('guest_office_registration_step_2');
            }
        }

        $form = $this->createForm(SignUpOfficeRegistrationForm::class, $registrationStudent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->beginTransaction();

            try {
                $lockedSlot = $this->em->find(
                    OfficeRegistration::class,
                    $id->id,
                    LockMode::PESSIMISTIC_WRITE,
                );

                if (!$lockedSlot) {
                    $this->em->rollback();

                    $this->addFlash('error', $this->translator->trans('Termin nie istnieje'));

                    return $this->redirectToRoute('guest_office_registration_step_2');
                }

                $activeRegistration = $this->officeRegistrationRegisteredStudentRepository
                    ->findActiveByRegistration($lockedSlot);

                if ($activeRegistration && $activeRegistration->student?->id !== $user->id) {
                    $this->em->rollback();

                    $this->addFlash('error', $this->translator->trans('Termin jest już zajęty'));

                    return $this->redirectToRoute('guest_office_registration_step_2');
                }

                $registrationStudent = $this->officeRegistrationRegisteredStudentRepository
                    ->findPendingForStudent($lockedSlot, $user);

                if (!$registrationStudent) {
                    $this->em->rollback();

                    throw $this->createAccessDeniedException('Brak dostępu do tej wizyty');
                }

                $wasAlreadyCompleted = $registrationStudent->meetingMode !== null
                    && $registrationStudent->description !== null;

                $data = $form->getData();

                $this->officeRegistrationRegisteredStudentRepository->update($data);

                $this->em->flush();
                $this->em->commit();

                if (!$wasAlreadyCompleted) {
                    $mailContent = NewOfficeRegistration::fromEntity($user, $lockedSlot);
                    $this->mailerService->sendEmailToStudent($user, $mailContent);
                }

                $this->addFlash('success', $this->translator->trans('Prośba o rezerwację wizyty została wysłana i oczekuje na potwierdzenie przez pracownika BON'));

                return $this->redirectToRoute('guest_office_registration_step_4', [
                    'id' => $lockedSlot->id,
                ]);
            } catch (\Throwable $exception) {
                $this->em->rollback();

                throw $exception;
            }
        }

        return $this->render('guest/office-registration/step-3.html.twig', [
            'registration' => $registrationStudent,
            'form' => $form->createView(),
            'officeRegistration' => $id,
        ]);
    }

    #[Route('/gosc/zapisy-bon/krok-4/{id}', name: 'guest_office_registration_step_4')]
    #[IsGranted('ROLE_GOSC')]
    public function summaryTerm(OfficeRegistration $id): Response
    {
        $user = $this->getUser();
        $myRegistration = null;

        $myRegistration = $this->officeRegistrationRegisteredStudentRepository->findOneBy([
            'registration' => $id,
            'student' => $user,
        ]);

        if (
            !$myRegistration
            || $myRegistration->meetingMode === null
            || $myRegistration->description === null
        ) {
            throw $this->createAccessDeniedException('Brak dostępu do tej wizyty');
        }

        return $this->render('guest/office-registration/step-4.html.twig', [
            'registration' => $id,
            'myRegistration' => $myRegistration,
        ]);
    }

    #[Route('/gosc/zapisy-bon/anuluj-rezerwacje/{id}', name: 'guest_office_registration_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_GOSC')]
    public function cancelTerm(OfficeRegistrationRegisteredStudent $id, Request $request): Response
    {
        if (!$id) {
            throw $this->createNotFoundException($this->translator->trans('Nie znaleziono wizyty'));
        }

        if (!$this->isCsrfTokenValid('cancel_registration_' . $id->id, $request->request->get('_token'),
        )) {
            throw $this->createAccessDeniedException(
                $this->translator->trans('Nie udało się zweryfikować bezpieczeństwa żądania. Odśwież stronę i spróbuj ponownie'),
            );
        }

        $user = $this->getUser();

        if ($id->student?->id !== $user->id) {
            throw $this->createAccessDeniedException('Brak dostępu do tej wizyty');
        }

        $now = new \DateTimeImmutable();
        $term = $id->registration->startAt;

        $canCancel = false;

        if ($term instanceof \DateTimeInterface) {
            $deadline = \DateTimeImmutable::createFromInterface($term)->modify('-24 hours');
            $canCancel = $now < $deadline;
        }

        if (!$canCancel) {
            $this->addFlash('danger', $this->translator->trans('Termin na anulowanie wizyty już minął'));

            return $this->redirectToRoute('guest_registration_center_my_registrations');
        }

        $id->confirmed = false;

        $this->em->flush();

        $this->addFlash('success', $this->translator->trans('Rezerwacja anulowana'));

        return $this->redirectToRoute('guest_registration_center_my_registrations');
    }

    /**
     * @throws DateMalformedStringException
     */
    #[Route('/gosc/api/office-terms', name: 'guest_office_terms', methods: ['GET'])]
    #[IsGranted('ROLE_GOSC')]
    public function guestOfficeTerms(Request $request): JsonResponse
    {
        $date = (string)$request->query->get('date');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->json(['slots' => []]);
        }

        try {
            $start = new \DateTimeImmutable($date . ' 00:00:00');
        } catch (\Throwable) {
            return $this->json(['slots' => []]);
        }

        $end = $start->modify('+1 day');
        $now = (new \DateTimeImmutable())->modify('+12 hours');

        $qb = $this->em->getRepository(OfficeRegistration::class)
            ->createQueryBuilder('s');

        $slots = $qb
            ->leftJoin(
                's.registeredStudents',
                'rs',
                'WITH',
                '(rs.confirmed = true) OR (rs.confirmed IS NULL AND rs.meetingMode IS NOT NULL)',
            )
            ->andWhere('rs.id IS NULL')
            ->andWhere('s.startAt >= :start')
            ->andWhere('s.startAt < :end')
            ->andWhere('s.startAt >= :now')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('now', $now)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();

        $out = array_map(static function (OfficeRegistration $s) {
            return [
                'id' => $s->id,
                'time' => $s->startAt->format('H:i') . ' - ' . $s->endAt->format('H:i'),
            ];
        }, $slots);

        return $this->json(['slots' => $out]);
    }

    #[Route('/gosc/api/office-terms/days', name: 'guest_office_terms_days', methods: ['GET'])]
    #[IsGranted('ROLE_GOSC')]
    public function officeTermsDays(Request $request): JsonResponse
    {
        $month = (string)$request->query->get('month');

        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $this->json(['days' => []]);
        }

        try {
            $start = new \DateTimeImmutable($month . '-01 00:00:00');
        } catch (\Throwable) {
            return $this->json(['days' => []]);
        }

        $end = $start->modify('first day of next month');
        $now = (new \DateTimeImmutable())->modify('+12 hours');

        $qb = $this->em->getRepository(OfficeRegistration::class)
            ->createQueryBuilder('s');

        $rows = $qb
            ->select('s.startAt')
            ->leftJoin(
                's.registeredStudents',
                'rs',
                'WITH',
                '(rs.confirmed = true) OR (rs.confirmed IS NULL AND rs.meetingMode IS NOT NULL)',
            )
            ->andWhere('rs.id IS NULL')
            ->andWhere('s.startAt >= :start')
            ->andWhere('s.startAt < :end')
            ->andWhere('s.startAt >= :now')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('now', $now)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $set = [];
        foreach ($rows as $row) {
            if (!isset($row['startAt']) || !$row['startAt'] instanceof \DateTimeInterface) {
                continue;
            }

            $set[$row['startAt']->format('Y-m-d')] = true;
        }

        return $this->json(['days' => array_keys($set)]);
    }
}
