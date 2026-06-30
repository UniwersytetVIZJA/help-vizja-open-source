<?php

namespace App\Controller\Student;

use App\Core\Application\ApplicationRepository;
use App\Core\Student\StudentManager;
use App\Database\Entity\Student;
use App\Database\Repository\RegisteredStudentRepository;
use App\Database\Repository\RegistrationRepository;
use App\Form\StudentProfile\UpdateDataForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProfileController extends AbstractController
{
    /**
     * @param RegistrationRepository $meetingRepository
     * @param EntityManagerInterface $entityManager
     * @param SecurityController $securityController
     * @param RegistrationRepository $registrationRepository
     * @param RegisteredStudentRepository $registeredStudentRepository
     * @param ApplicationRepository $applicationRepository
     * @param StudentManager $studentManager
     */
    public function __construct(
        private readonly RegistrationRepository $meetingRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SecurityController $securityController,
        private readonly RegistrationRepository $registrationRepository, private readonly RegisteredStudentRepository $registeredStudentRepository, private readonly ApplicationRepository $applicationRepository, private readonly StudentManager $studentManager,
    ) {}

    /**
     * Wyświetla profil studenta wraz z podsumowaniem aktywności.
     *
     * Metoda prezentuje podstawowe informacje o koncie studenta,
     * w tym ostatni wniosek, najbliższy zapis oraz statystyki aktywności.
     *
     * @param Request $request Żądanie HTTP
     *
     * @return Response Widok profilu studenta
     */
    #[Route('/profil', name: 'student_profile_index')]
    public function index(Request $request): Response
    {
        $studentId = $this->getUser()->getUserIdentifier();

        $latestApplication = $this->applicationRepository->findLatestProfile($studentId);
        $latestRegistration = $this->registeredStudentRepository->findNextForStudent($studentId);

        $countApplication = $this->applicationRepository->countByStudent($studentId);
        $countRegistration = $this->registeredStudentRepository->countByStudent($studentId);

        $allRegistration = $this->registrationRepository->countAllActive();

        return $this->render('student/profile/user-profile.html.twig', [
            'latestApplication' => $latestApplication,
            'latestRegistration' => $latestRegistration,
            'countApplication' => $countApplication,
            'countRegistration' => $countRegistration,
            'allRegistration' => $allRegistration,
        ]);
    }

    /**
     * Wyświetla formularz edycji danych profilu studenta oraz zapisuje zmiany.
     *
     * Metoda umożliwia aktualizację danych osobowych studenta
     * i po poprawnym zapisie przekierowuje do strony profilu.
     *
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza edycji lub przekierowanie po zapisie
     */
    #[Route('/profil/uzupełnij-dane', name: 'student_profile_update_data')]
    #[IsGranted('ROLE_STUDENT')]
    public function updateData(Request $request): Response
    {
        $studentEnity = $this->getUser();

        $form = $this->createForm(UpdateDataForm::class, $studentEnity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->studentManager->update($data);

            return $this->redirectToRoute('student_profile_index');
        }

        return $this->render('student/profile/update-data-form.html.twig', [
            'form' => $form->createView(),
        ]);
    }



}
