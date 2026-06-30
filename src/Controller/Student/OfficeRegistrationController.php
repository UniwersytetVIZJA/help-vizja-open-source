<?php

namespace App\Controller\Student;

use App\Core\Student\StudentManager;
use App\Database\Entity\OfficeRegistration;
use App\Database\Entity\OfficeRegistrationRegisteredStudent;
use App\Database\Repository\OfficeRegistrationRegisteredStudentRepository;
use App\Database\Repository\OfficeRegistrationRepository;
use App\Form\OfficeRegistration\OfficeRegistrationForm;
use App\Form\OfficeRegistration\SignUpOfficeRegistrationForm;
use App\Mailer\Mail\OfficeRegistration\NewOfficeRegistration;
use App\Mailer\MailerService;
use DateMalformedStringException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function array_keys;
use function array_map;
use function preg_match;

class OfficeRegistrationController extends AbstractController
{
    public function __construct(private readonly StudentManager $studentManager, private readonly EntityManagerInterface $entityManager, private readonly OfficeRegistrationRepository $officeRegistrationRepository, private readonly TranslatorInterface $translator, private readonly OfficeRegistrationRegisteredStudentRepository $officeRegistrationRegisteredStudentRepository,
        private readonly MailerService $mailerService,
    ) {}

    #[Route('/zapisy-bon', name: 'student_office_registration_menu')]
    #[IsGranted('ROLE_STUDENT')]
    public function consultation(): Response
    {
        return $this->render('student/office-registration/menu.html.twig');
    }

    #[Route('/zapisy-bon/krok-1', name: 'student_office_registration_step_1')]
    #[IsGranted('ROLE_STUDENT')]
    public function chooseDate(Request $request): Response
    {
        $form = $this->createForm(OfficeRegistrationForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $termId = $form->get('termId')->getData();

            if (!$termId) {
                $this->addFlash('error', $this->translator->trans('Wybierz godzinę wizyty.'));

                return $this->redirectToRoute('student_office_registration_step_1');
            }
            $term = $this->officeRegistrationRepository->find($termId);
            if (!$term) {
                $this->addFlash('error', $this->translator->trans('Ten termin nie istnieje. Wybierz inny.'));

                return $this->redirectToRoute('student_office_registration_step_1');
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

                return $this->redirectToRoute('student_office_registration_step_1');
            }

            $slot = $term;

            $activeRegistration = $this->officeRegistrationRegisteredStudentRepository->findOneBy([
                'registration' => $slot,
                'confirmed' => true,
            ]);

            if ($activeRegistration) {
                $this->addFlash('error', $this->translator->trans('Ten termin nie jest dostępny. Wybierz inny.'));

                return $this->redirectToRoute('student_office_registration_step_1');
            }

            $registrationStudent = new OfficeRegistrationRegisteredStudent();
            $registrationStudent->registration = $slot;
            $registrationStudent->student = $this->getUser();

            $this->entityManager->persist($registrationStudent);
            $this->entityManager->flush();

            return $this->redirectToRoute('student_office_registration_step_2', [
                'id' => $slot->id,
            ]);
        }

        return $this->render('student/office-registration/step-1.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws DateMalformedStringException
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/zapisy-bon/krok-2/{id}', name: 'student_office_registration_step_2')]
    #[IsGranted('ROLE_STUDENT')]
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

                return $this->redirectToRoute('student_office_registration_step_1');
            }
        }

        $form = $this->createForm(SignUpOfficeRegistrationForm::class, $registrationStudent);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->beginTransaction();

            try {
                $lockedSlot = $this->entityManager->find(
                    OfficeRegistration::class,
                    $id->id,
                    LockMode::PESSIMISTIC_WRITE,
                );

                if (!$lockedSlot) {
                    $this->entityManager->rollback();

                    $this->addFlash('error', $this->translator->trans('Termin nie istnieje'));

                    return $this->redirectToRoute('student_office_registration_step_1');
                }

                $activeRegistration = $this->officeRegistrationRegisteredStudentRepository
                    ->findActiveByRegistration($lockedSlot);

                if ($activeRegistration && $activeRegistration->student->id !== $user->id) {
                    $this->entityManager->rollback();

                    $this->addFlash('error', $this->translator->trans('Termin jest już zajęty'));

                    return $this->redirectToRoute('student_office_registration_step_1');
                }

                $registrationStudent = $this->officeRegistrationRegisteredStudentRepository
                    ->findPendingForStudent($id, $user);

                if (!$registrationStudent) {
                    $this->entityManager->rollback();

                    throw $this->createAccessDeniedException('Brak dostępu do tej wizyty');
                }

                $wasAlreadyCompleted = $registrationStudent->meetingMode !== null
                    && $registrationStudent->description !== null;

                $data = $form->getData();

                $this->officeRegistrationRegisteredStudentRepository->update($data);

                $this->entityManager->flush();
                $this->entityManager->commit();

                if (!$wasAlreadyCompleted) {
                    $mailContent = NewOfficeRegistration::fromEntity($user, $lockedSlot);
                    $this->mailerService->sendEmailToStudent($user, $mailContent);
                }

                $this->addFlash('success', $this->translator->trans('Prośba o rezerwację wizyty została wysłana i oczekuje na potwierdzenie przez pracownika BON'));

                return $this->redirectToRoute('student_office_registration_step_3', [
                    'id' => $lockedSlot->id,
                ]);
            } catch (\Throwable $exception) {
                $this->entityManager->rollback();

                throw $exception;
            }
        }

        return $this->render('student/office-registration/step-2.html.twig', [
            'registration' => $registrationStudent,
            'form' => $form->createView(),
            'officeRegistration' => $id,
        ]);
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/zapisy-bon/krok-3/{id}', name: 'student_office_registration_step_3')]
    #[IsGranted('ROLE_STUDENT')]
    public function summaryTerm(OfficeRegistration $id): Response
    {
        $user = $this->getUser();

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

        return $this->render('student/office-registration/step-3.html.twig', [
            'registration' => $id,
            'myRegistration' => $myRegistration,
        ]);
    }

    #[Route('/zapisy-bon/moje-wizyty', name: 'student_registration_center_my_registrations')]
    #[IsGranted('ROLE_STUDENT')]
    public function myRegistrations(Request $request): Response
    {
        $user = $this->getUser();
        $registrations = $this->officeRegistrationRegisteredStudentRepository->findByStudent($user);

        $now = new \DateTimeImmutable();
        $cancelMap = [];

        foreach ($registrations as $registration) {
            $term = $registration->registration->startAt;
            $allowed = false;

            if ($term instanceof \DateTimeInterface) {
                $deadline = \DateTimeImmutable::createFromInterface($term)->modify('-24 hours');
                $allowed = $now < $deadline && $registration->confirmed !== false;
            }

            $cancelMap[$registration->id] = $allowed;
        }

        return $this->render('student/office-registration/my-registrations.html.twig', [
            'registrations' => $registrations,
            'canCancel' => $cancelMap,
        ]);
    }

    #[Route('/zapisy-bon/moje-wizyty/archiwum', name: 'student_registration_center_my_registrations_archive')]
    #[IsGranted('ROLE_STUDENT')]
    public function archiveRegistrations(Request $request): Response
    {
        $user = $this->getUser();
        $registrations = $this->officeRegistrationRepository->findByStudent($user);

        $registrationSort = $this->officeRegistrationRepository->findArchive($user);

        $now = new \DateTimeImmutable();
        $cancelMap = [];

        foreach ($registrations as $registration) {
            $term = $registration->startAt;

            $allowed = false;

            if ($term instanceof \DateTimeInterface) {
                $deadline = \DateTimeImmutable::createFromInterface($term)->modify('-24 hours');
                $allowed = $now < $deadline;
            }

            $cancelMap[$registration->id] = $allowed;
        }

        return $this->render('student/office-registration/archive-registrations.twig', [
            'registrations' => $registrationSort,
            'canCancell' => $cancelMap,
        ]);
    }

    #[Route('/zapisy-bon/anuluj-rezerwacje/{id}', name: 'student_office_registration_cancel', methods: ['POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function cancelTerm(Request $request, OfficeRegistrationRegisteredStudent $registrationStudent): Response
    {
        if (!$registrationStudent) {
            throw $this->createNotFoundException($this->translator->trans('Nie znaleziono wizyty'));
        }

        if (!$this->isCsrfTokenValid(
            'cancel_registration_' . $registrationStudent->id,
            $request->request->get('_token'),
        )) {
            throw $this->createAccessDeniedException();
        }

        $user = $this->getUser();

        if ($registrationStudent->student?->id !== $user->id) {
            throw $this->createAccessDeniedException('Brak dostępu do tej wizyty');
        }

        $term = $registrationStudent->registration->startAt;
        $now = new \DateTimeImmutable();
        $canCancel = false;

        if ($term instanceof \DateTimeInterface) {
            $deadline = \DateTimeImmutable::createFromInterface($term)->modify('-24 hours');
            $canCancel = $now < $deadline;
        }

        if (!$canCancel) {
            $this->addFlash('danger', $this->translator->trans('Termin na anulowanie wizyty już minął'));

            return $this->redirectToRoute('student_registration_center_my_registrations');
        }

        $registrationStudent->confirmed = false;
        $this->entityManager->flush();

        $this->addFlash('success', $this->translator->trans('Rezerwacja anulowana'));

        return $this->redirectToRoute('student_registration_center_my_registrations');
    }

    #[Route('/api/office-terms', name: 'student_office_terms', methods: ['GET'])]
    #[IsGranted('ROLE_STUDENT')]
    public function studentOfficeTerms(Request $request): JsonResponse
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

        $qb = $this->entityManager->getRepository(OfficeRegistration::class)
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

    #[Route('/api/office-terms/days', name: 'student_office_terms_days', methods: ['GET'])]
    #[IsGranted('ROLE_STUDENT')]
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

        $qb = $this->entityManager->getRepository(OfficeRegistration::class)
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
