<?php

namespace App\Controller\Admin;

use App\Controller\Pagination\PaginatorWith;
use App\Core\Application\ApplicationRepository;
use App\Core\Student\StudentManager;
use App\Core\Student\StudentRepository;
use App\Database\Entity\Student;
use App\Form\StudentProfile\AdaptationCardForm;
use App\Form\StudentProfile\DisabilityStatementForm;
use App\Form\StudentProfile\SpecialNeedsForm;
use App\Form\StudentProfile\UpdateDataForm;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class StudentController extends AbstractController
{
    /**
     * @param PaginatorInterface $paginator
     * @param StudentRepository $studentRepository
     * @param StudentManager $studentManager
     */
    public function __construct(
        private readonly PaginatorInterface $paginator, private readonly StudentRepository $studentRepository, private readonly StudentManager $studentManager, private readonly ApplicationRepository $applicationRepository, private readonly TranslatorInterface $translator
    ) {}

    /**
     * Wyświetla listę studentów w panelu administracyjnym.
     *
     * Umożliwia filtrowanie studentów na podstawie danych przekazanych
     * w parametrach zapytania oraz prezentuje wyniki z paginacją.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Request $request Żądanie HTTP zawierające parametry filtrowania i numer strony
     *
     * @return Response Widok listy studentów z paginacją
     */
    #[Route('/admin/studenci', name: 'admin_students')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function index(Request $request): Response
    {
        $this->studentRepository->getAllStudents();

        $students = $request->query->get('user', '');
        $query = $this->studentRepository->findFilter($students);

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($query, $page);

        return $this->render('admin/students/students.html.twig', [
            'users' => $pagination,
            'pagination' => $pagination,
            'filter' => [
                'user' => $students,
            ],
        ]);
    }

    /**
     * Blokuje konto wybranego studenta.
     *
     * Metoda zmienia status konta studenta na zablokowane
     * i przekierowuje do listy studentów.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Student $studentId Student, którego konto ma zostać zablokowane
     *
     * @return Response Przekierowanie po zablokowaniu konta
     */
    #[Route('/admin/studenci/zablokuj/{id}', name: 'admin_students_block')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function blockAccount(Student $studentId): Response
    {
        if($studentId){
        $this->studentManager->block($studentId);
        }else{
            $this->addFlash('danger', $this->translator->trans('Nie wykryto studenta'));
            return $this->redirectToRoute('admin_students');
        }

        $this->addFlash('success', $this->translator->trans('Konto studenta zostało zablokowane'));

        return $this->redirectToRoute('admin_students');
    }

    /**
     * Przywraca (aktywuje) konto wybranego studenta.
     *
     * Metoda zmienia status konta studenta na aktywne
     * i przekierowuje do listy studentów.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Student $student Student, którego konto ma zostać przywrócone
     *
     * @return Response Przekierowanie po aktywacji konta
     */
    #[Route('/admin/studenci/aktywuj/{id}', name: 'admin_students_restore')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function restoreAccount(Student $student): Response
    {
        $this->studentManager->restore($student);

        $this->addFlash('success', $this->translator->trans('Konto studenta zostało aktywowane'));

        return $this->redirectToRoute('admin_students');
    }

    #[Route('/admin/studenci/profil/{id}', name: 'admin_students_profile')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function studentProfile(Student $id, Request $request): Response
    {
        $student = $this->studentRepository->getOneById($id->id);

        $applications = $this->applicationRepository->findByStudent($student->id);

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($applications, $page);

        return $this->render('admin/students/profile.html.twig', [
            'student' => $student,
            'pagination' => $pagination,
        ]);
    }

    #[Route('/admin/studenci/profil/{id}/edytuj-studia', name: 'admin_students_profile_update_study')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateStudy(Student $id, Request $request): Response
    {
        $student = $this->studentRepository->getOneById($id->id);

        $form = $this->createForm(UpdateDataForm::class, $student);
        $form->remove('phone');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->studentManager->update($data);

            return $this->redirectToRoute('admin_students_profile', [
                'id' => $student->id,
            ]);
        }

        return $this->render('admin/students/update-study.html.twig', [
            'student' => $student,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/studenci/profil/{id}/edytuj-kontakt', name: 'admin_students_profile_update_contact')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updatecontact(Student $id, Request $request): Response
    {
        $student = $this->studentRepository->getOneById($id->id);

        $form = $this->createForm(UpdateDataForm::class, $student);
        $form->remove('albumNumber');
        $form->remove('department');
        $form->remove('faculty');
        $form->remove('studyYear');
        $form->remove('studySemester');
        $form->remove('studyMode');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->studentManager->update($data);

            return $this->redirectToRoute('admin_students_profile', [
                'id' => $student->id,
            ]);
        }

        return $this->render('admin/students/update-contact.html.twig', [
            'student' => $student,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/studenci/profil/{id}/edytuj-specjalne-potrzeby', name: 'admin_students_profile_update_special_needs')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateSpecialNeeds(Student $id, Request $request): Response
    {
        $student = $this->studentRepository->getOneById($id->id);

        $form = $this->createForm(SpecialNeedsForm::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->studentManager->update($data);

            return $this->redirectToRoute('admin_students_profile', [
                'id' => $student->id,
            ]);
        }

        return $this->render('admin/students/update-special-needs.html.twig', [
            'student' => $student,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/studenci/profil/{id}/edytuj-orzeczenie', name: 'admin_students_profile_update_disability_statement')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateDisabilityStatement(Student $id, Request $request): Response
    {
        $student = $this->studentRepository->getOneById($id->id);

        $form = $this->createForm(DisabilityStatementForm::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->studentManager->update($data);

            return $this->redirectToRoute('admin_students_profile', [
                'id' => $student->id,
            ]);
        }

        return $this->render('admin/students/update-disability-statement.html.twig', [
            'student' => $student,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/studenci/profil/{id}/edytuj-karte-adaptacji', name: 'admin_students_profile_update_adaptation_card')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateAdaptationCard(Student $id, Request $request): Response
    {
        $student = $this->studentRepository->getOneById($id->id);

        $form = $this->createForm(AdaptationCardForm::class, $student);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->studentManager->update($data);

            return $this->redirectToRoute('admin_students_profile', [
                'id' => $student->id,
            ]);
        }

        return $this->render('admin/students/update-adaptation-card.html.twig', [
            'student' => $student,
            'form' => $form->createView(),
        ]);
    }
}
