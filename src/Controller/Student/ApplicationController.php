<?php

namespace App\Controller\Student;

use App\Core\Application\ApplicationManager;
use App\Core\Application\ApplicationRepository;
use App\Core\DictionaryItem\DictionaryItemManager;
use App\Core\DictionaryItem\DictionaryItemRepository;
use App\Core\File\FileManager;
use App\Core\File\FileUploader;
use App\Core\Student\StudentManager;
use App\Core\Student\StudentRepository;
use App\Database\Entity\Application;
use App\Database\Entity\File;
use App\Database\Repository\ApplicationFinder;
use App\Database\Repository\FileRepository;
use App\Enum\Application\ApplicationDiscrEnum;
use App\Enum\Dictionary\DictionaryNameEnum;
use App\Form\Application\ApplicationAdaptationForm;
use App\Form\Application\ApplicationTypeForm;
use App\Form\Application\ApplicationTypeFormHelper;
use App\Form\ApplicationFileTypeEnum;
use App\Mailer\Mail\Application\StudentSubmitApplication;
use App\Mailer\MailerService;
use App\Security\Voter\ApplicationVoter;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Exercise\HTMLPurifierBundle\HTMLPurifiersRegistryInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function count;
use function in_array;

/**
 * Class ApplicationController
 * @package App\Controller\Student
 */
class ApplicationController extends AbstractController
{
    /**
     * ApplicationController constructor
     * @param ApplicationManager $applicationManager
     * @param DictionaryItemManager $dictionaryItemManager
     * @param ApplicationRepository $applicationRepository
     * @param FileManager $fileManager
     * @param FileRepository $fileRepository
     * @param StudentRepository $studentRepository
     * @param FileUploader $fileUploader
     * @param ApplicationFinder $finder
     * @param MailerService $mailerService
     * @param HTMLPurifiersRegistryInterface $purifier
     * @param StudentManager $studentManager
     * @param EntityManagerInterface $entityManager
     * @param PaginatorInterface $paginator
     */
    public function __construct(
        private readonly ApplicationManager $applicationManager,
        private readonly DictionaryItemManager $dictionaryItemManager,
        private readonly ApplicationRepository $applicationRepository,
        private readonly FileManager $fileManager,
        private readonly FileRepository $fileRepository,
        private readonly StudentRepository $studentRepository,
        private readonly FileUploader $fileUploader,
        private readonly ApplicationFinder $finder,
        private readonly MailerService $mailerService,
        private readonly HTMLPurifiersRegistryInterface $purifier, private readonly StudentManager $studentManager, private readonly EntityManagerInterface $entityManager, private readonly PaginatorInterface $paginator, private readonly DictionaryItemRepository $dictionaryItemRepository, private readonly TranslatorInterface $translator, private readonly ValidatorInterface $validator,
    ) {}

    /**
     * Wyświetla listę wniosków zalogowanego studenta.
     *
     * Metoda prezentuje wnioski przypisane do bieżącego użytkownika
     * oraz udostępnia listę dostępnych typów wniosków.
     *
     * @param Request $request Żądanie HTTP zawierające numer strony
     *
     * @return Response Widok listy wniosków z paginacją
     */
    #[Route('/wnioski', name: 'student_application_index')]
    #[IsGranted('ROLE_STUDENT')]
    public function index(Request $request): Response
    {
        $student = $this->getUser();
        $applications = $this->applicationRepository->findByStudent($student->getId());
        $applicationTypes = $this->dictionaryItemManager->findAllByDictionaryName(DictionaryNameEnum::TYPY_WNIOSKOW);

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($applications, $page, 5);

        return $this->render('student/application/index.html.twig', [
            'applciationTypes' => $applicationTypes,
            'applications' => $applications,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Wyświetla formularz wyboru typu wniosku i inicjuje proces jego tworzenia.
     *
     * Metoda obsługuje pierwszy krok składania wniosku przez studenta.
     * Po poprawnym wyborze typu tworzony jest nowy wniosek, a użytkownik
     * zostaje przekierowany do kolejnego kroku (z adaptacjami lub formularzem wniosku).
     *
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza wyboru typu lub przekierowanie do kolejnego kroku
     */
    #[Route('/wnioski/dodaj/krok/1', name: 'student_choose_application_type')]
    #[IsGranted('ROLE_STUDENT')]
    public function chooseApplicationType(Request $request): Response
    {
        $form = $this->createForm(ApplicationTypeForm::class);

        $form->handleRequest($request);
        $application = $form->getData();

        if ($form->isSubmitted() && $form->isValid()) {
            $application = $form->getData();
            $this->applicationManager->create($application);

            $type = $application->type->hiddenValue;
            $adaptationRequiredTypes = [ApplicationDiscrEnum::LANGUAGE_INTERPRETER->value, ApplicationDiscrEnum::TEACHING_ASSISTANT->value];
            $adaptationRequired = in_array($type, $adaptationRequiredTypes, true);

            $request->getSession()->set('application_adaptation_required', $adaptationRequired);

            if ($adaptationRequired) {
                return $this->redirectToRoute('student_choose_adaptation');
            }

            return $this->redirectToRoute('student_fill_application_form');
        }

        return $this->render('student/application/step-1.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Wyświetla formularz wyboru adaptacji i kieruje użytkownika do kolejnego kroku.
     *
     * Metoda pobiera aktualnie tworzony wniosek z sesji, obsługuje wybór adaptacji
     * i na tej podstawie przekierowuje do odpowiedniego etapu kreatora
     * (np. ponowny wybór typu wniosku lub uzupełnienie formularza).
     *
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok wyboru adaptacji lub przekierowanie do kolejnego kroku
     *
     * @throws \LogicException Gdy przesłano nieobsługiwany typ adaptacji
     */
    #[Route('/wnioski/dodaj/krok/1/wybierz-adaptacje', name: 'student_choose_adaptation')]
    #[IsGranted('ROLE_STUDENT')]
    public function chooseAdaptation(Request $request): Response
    {
        $application = $this->applicationManager->getApplicationFromSession();

        if (!$application) {
            return $this->redirectToRoute('student_choose_application_type');
        }

        $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $application);

        $form = $this->createForm(ApplicationAdaptationForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $adaptation = (int)$form->get('adaptation')->getData();

            if ($adaptation === 1) {
                $this->applicationManager->removeById($application->getId());

                return $this->redirectToRoute('student_choose_application_type_b', [
                    'adaptationType' => $application->type->value,
                ]);
            }

            if ($adaptation === 2) {
                return $this->redirectToRoute('student_fill_application_form');
            }

            throw new \LogicException('Nieznany typ adaptacji');
        }

        return $this->render('student/application/step-1-choose-adaptation.html.twig', [
            'application' => $application,
            'form' => $form->createView(),
            'adaptationRequired' => $request->getSession()->get('application_adaptation_required', false),
        ]);
    }

    /**
     * Umożliwia ponowny wybór typu wniosku w alternatywnym kroku kreatora.
     *
     * Metoda obsługuje wariantowy etap wyboru typu wniosku (krok 1b),
     * tworzy nowy wniosek na podstawie danych formularza
     * i przekierowuje użytkownika do formularza wniosku.
     *
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza wyboru typu lub przekierowanie do kolejnego kroku
     */
    #[Route('/wnioski/dodaj/krok/1b/wybierz-typ-wniosku', name: 'student_choose_application_type_b')]
    #[IsGranted('ROLE_STUDENT')]
    public function chooseApplicationTypeB(Request $request): Response
    {
        $application = new Application\EducationalProcess();

        $form = $this->createForm(ApplicationTypeFormHelper::class, $application);
        $form->handleRequest($request);

        $adaptationTypeFromUrl = $request->query->get('adaptationType');

        $adaptationType = $adaptationTypeFromUrl;

        if ($adaptationTypeFromUrl) {
            $adaptation = $this->dictionaryItemRepository->findOneBy([
                'value' => $adaptationTypeFromUrl,
            ]);

            if ($adaptation) {
                $adaptationType = $request->getLocale() === 'en'
                    ? $adaptation->valueEnglish
                    : $adaptation->value;
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $educationalProcessType = $this->dictionaryItemRepository
                ->findByType('educational-process')
                ->getQuery()
                ->getOneOrNullResult();

            $application->type = $educationalProcessType;

            $this->applicationManager->create($application);

            $request->getSession()->set('application_adaptation_required', false);

            return $this->redirectToRoute('student_fill_application_form');
        }

        return $this->render('student/application/test.html.twig', [
            'form' => $form->createView(),
            'adaptationType' => $adaptationType,
            'adaptationRequired' => $request->getSession()->get('application_adaptation_required', false),
        ]);
    }

    /**
     * Wyświetla formularz uzupełniania danych wniosku (krok 2) i obsługuje jego zapis.
     *
     * Metoda pobiera aktualnie tworzony wniosek z sesji, uzupełnia go danymi studenta,
     * obsługuje przesłane załączniki, nadaje numer wniosku oraz aktualizuje dane studenta.
     * Po poprawnym zapisaniu przekierowuje do kroku wysyłki wniosku.
     *
     * @param Request $request Żądanie HTTP zawierające dane formularza i załączniki
     *
     * @return Response Widok formularza kroku 2 lub przekierowanie do kolejnego kroku
     * @throws \Doctrine\DBAL\Exception
     * @throws Throwable
     */
    #[Route('/wnioski/dodaj/krok/2', name: 'student_fill_application_form')]
    #[IsGranted('ROLE_STUDENT')]
    public function fillApplicationForm(Request $request): Response
    {
        $student = $this->getUser();

        $application = $this->applicationManager->getApplicationFromSession();
        if (!$application) {
            return $this->redirectToRoute('student_choose_application_type');
        }

        $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $application);

        if ($application?->id) {
            $application = $this->applicationRepository->find($application->id);
        }

        $this->applicationRepository->getInfoFromStudent($student, $application);

        $filesCounts = $this->applicationManager->getApplicationFilesCounts($application);

        $form = $this->applicationManager->createFormByType($application, [
            'files_counts' => $filesCounts,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $checkVerbisInfo = $this->studentRepository->hasAllVerbisData($student->id);

            if (!$checkVerbisInfo) {
                $this->addFlash('danger', $this->translator->trans('Zaktualizuj swoje dane z Verbisa'));

                return $this->redirectToRoute('student_fill_application_form');
            }

            $fileConstraints = new Assert\File(
                maxSize: '10M',
                mimeTypes: ['application/pdf'],
                maxSizeMessage: 'Plik może mieć maksymalnie 10 MB',
                mimeTypesMessage: 'Obsługiwany format pliku to PDF',
            );

            foreach ($application->files as $file) {
                if ($file instanceof File) {
                    $file->application = $application;
                }
            }

            $hasFileErrors = false;
            $hasSavedFiles = false;

            foreach (['decision', 'schedule', 'statement', 'files'] as $fieldName) {
                if (!$form->has($fieldName)) {
                    continue;
                }

                foreach ($form->get($fieldName)->getData() ?? [] as $fileEntity) {
                    if (!$fileEntity instanceof File) {
                        continue;
                    }

                    if ($fileEntity->file === null) {
                        continue;
                    }

                    $fileEntity->application = $application;
                    $fileEntity->category = ApplicationFileTypeEnum::APPLICATION_STUDENT_ATTACHMENT->value;
                    $fileEntity->category2 = $fieldName;

                    $violations = $this->validator->validate($fileEntity->file, $fileConstraints);

                    if (count($violations) > 0) {
                        $hasFileErrors = true;

                        foreach ($violations as $violation) {
                            $form->get($fieldName)->addError(
                                new FormError($violation->getMessage()),
                            );
                        }

                        $this->entityManager->detach($fileEntity);

                        continue;
                    }

                    $this->fileManager->create($fileEntity);
                    $application->addFile($fileEntity);

                    $hasSavedFiles = true;
                }
            }

            if ($hasSavedFiles) {
                $this->entityManager->flush();
                $this->entityManager->refresh($application);
            }

            if (!$hasFileErrors && $form->isValid()) {
                $application->department = $student->wydzialVerbis;
                $application->faculty = $student->kierunekVerbis;
                $application->albumNumber = $student->albumNumber;
                $application->studyMode = $student->trybZajecVerbis;
                $application->year = $student->rokStudiowVerbis;
                $application->semester = $student->semestrVerbis;
                $application->dean = $this->dictionaryItemRepository
                    ->findOneByDictionaryNameAndValue(
                        DictionaryNameEnum::WYDZIAL,
                        $student->wydzialVerbis,
                    )?->valueKey;

                $this->entityManager->persist($application);

                $this->entityManager->flush();

                return $this->redirectToRoute('student_send_application_form');
            }

            $filesCounts = $this->getApplicationFilesCounts($application);
        }

        return $this->render('student/application/step-2.html.twig', [
            'application' => $application,
            'form' => $form->createView(),
            'adaptationRequired' => $request->getSession()->get('application_adaptation_required', false),
        ]);
    }

    private function getApplicationFilesCounts(Application $application): array
    {
        $filesCounts = [
            'files' => 0,
            'decision' => 0,
            'schedule' => 0,
            'statement' => 0,
        ];

        foreach ($application->files as $file) {
            $category = $file->category2 ?? 'files';

            if (isset($filesCounts[$category])) {
                $filesCounts[$category]++;
            }
        }

        return $filesCounts;
    }

    /**
     * Wyświetla podsumowanie wniosku i obsługuje jego wysłanie (krok 3).
     *
     * Metoda prezentuje dane wniosku wraz z załącznikami,
     * a po potwierdzeniu zapisuje wniosek i przekierowuje
     * do strony podsumowania.
     *
     * @param Request $request Żądanie HTTP (GET – podgląd, POST – wysłanie)
     *
     * @return Response Widok podsumowania lub przekierowanie po wysłaniu
     */
    #[Route('/wnioski/dodaj/krok/3', name: 'student_send_application_form')]
    #[IsGranted('ROLE_STUDENT')]
    public function sendApplicationForm(Request $request): Response
    {
        $application = $this->applicationManager->getApplicationFromSession();
        if (!$application) {
            return $this->redirectToRoute('student_choose_application_type');
        }
        $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $application);

        $files = $this->fileRepository->findBy(['application' => $application->getId()]);

        $file = $files[0] ?? null;

        if ($file) {
            $sizeBytes = $this->fileRepository->fileSize($file);
            $fileKb = $this->fileRepository->fileSizeInKB($sizeBytes);
        }

        if ($request->getMethod() === 'POST') {
            if (!$this->isCsrfTokenValid(
                'submit_application_' . $application->getId(),
                $request->request->get('_token'),
            )) {
                throw $this->createAccessDeniedException(
                    $this->translator->trans('Nie udało się zweryfikować bezpieczeństwa żądania. Odśwież stronę i spróbuj ponownie'),
                );
            }

            $this->applicationManager->update($application);

            return $this->redirectToRoute('student_show_application_summary');
        }

        return $this->render('student/application/step-3.html.twig', [
            'application' => $application,
            'files' => $files,
            'fileSize' => $fileKb ?? null,
            'adaptationRequired' => $request->getSession()->get('application_adaptation_required', false),
        ]);
    }

    /**
     * Wyświetla końcowe podsumowanie złożonego wniosku (krok 4).
     *
     * Metoda prezentuje podsumowanie wniosku, czyści dane wniosku z sesji
     * oraz wysyła do studenta wiadomość e-mail z potwierdzeniem złożenia.
     *
     * @return Response Widok końcowego podsumowania wniosku
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route('/wnioski/dodaj/krok/4', name: 'student_show_application_summary')]
    #[IsGranted('ROLE_STUDENT')]
    public function showApplicationSummary(Request $request): Response
    {
        $application = $this->applicationManager->getApplicationFromSession();
        if (!$application) {
            return $this->redirectToRoute('student_choose_application_type');
        }
        $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $application);

        if ($application->applicationNumber === null) {
            $this->applicationManager->assignApplicationNumber($application);
        }

        $adaptationRequired = $request->getSession()->get('application_adaptation_required', false);

        $this->applicationManager->clearSession();

        $request->getSession()->remove('application_adaptation_required');

        $student = $application->student;

        if ($application->studentSubmitEmailSentAt === null) {
            $mailContent = StudentSubmitApplication::fromEntity($student, $application);
            $this->mailerService->sendEmailToStudent($student, $mailContent);

            $application->studentSubmitEmailSentAt = new \DateTimeImmutable();
            $this->entityManager->flush();
        }

        return $this->render('student/application/step-4.html.twig', [
            'application' => $application,
            'adaptationRequired' => $adaptationRequired,
        ]);
    }

    /**
     * Umożliwia pobranie załącznika przypisanego do wniosku.
     *
     * @param string $fileId Identyfikator pliku
     *
     * @return BinaryFileResponse Odpowiedź z plikiem do pobrania
     */
    #[Route('/wnioski/pobierz-plik/{fileId}', name: 'student_application_file_download')]
    #[IsGranted('ROLE_STUDENT')]
    public function downloadFile(string $fileId): BinaryFileResponse
    {
        $file = $this->fileRepository->find($fileId);

        if (!$file) {
            throw $this->createNotFoundException('Plik nie istnieje');
        }

        $application = $file->application;

        $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $application);

        $filePath = $this->fileManager->getFilePath($file);

        return $this->file(
            $filePath,
            $file->originalName,
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        );
    }

    /**
     * Wyświetla załącznik wniosku w przeglądarce.
     *
     * Metoda sprawdza uprawnienia dostępu do wniosku
     * i udostępnia plik w trybie podglądu (online).
     *
     * @param string $fileId Identyfikator pliku
     *
     * @return BinaryFileResponse Odpowiedź z plikiem do wyświetlenia
     */
    #[Route('/wnioski/wyswietl-plik/{fileId}', name: 'student_application_file_show')]
    #[IsGranted('ROLE_STUDENT')]
    public function showFile(string $fileId): BinaryFileResponse
    {
        $file = $this->fileRepository->find($fileId);

        if (!$file) {
            throw $this->createNotFoundException('Plik nie istnieje');
        }

        $application = $file->application;

        $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $application);

        $filePath = $this->fileManager->getFilePath($file);

        return $this->file(
            $filePath,
            $file->fileName,
            ResponseHeaderBag::DISPOSITION_INLINE,
        );
    }

    /**
     * Wyświetla szczegóły wniosku studenta.
     *
     * Metoda sprawdza uprawnienia dostępu do wniosku,
     * zapisuje informację o wyświetleniu szczegółów
     * oraz prezentuje dane wniosku wraz z załącznikami.
     *
     * @param string $applicationId Identyfikator wniosku
     *
     * @return Response Widok szczegółów wniosku
     */
    #[Route('/wnioski/szczegoly/{applicationId}', name: 'student_application_details')]
    #[IsGranted('ROLE_STUDENT')]
    public function viewApplication(string $applicationId): Response
    {
        $application = $this->applicationRepository->findOneById($applicationId);

        if (!$application) {
            throw $this->createNotFoundException('Wniosek nie istnieje');
        }

        $this->denyAccessUnlessGranted(ApplicationVoter::VIEW, $application);

        $application->applicationDetailsSeen = new \DateTimeImmutable();
        $this->entityManager->persist($application);
        $this->entityManager->flush();

        $files = $this->fileRepository->findBy([
            'application' => $application,
            'category' => ApplicationFileTypeEnum::APPLICATION_STUDENT_ATTACHMENT->value,
        ]);

        $employeeFile = $this->fileRepository->findBy([
            'application' => $application,
            'category' => ApplicationFileTypeEnum::APPLICATION_EMPLOYEE_ATTACHMENT->value,
        ]);

        $form = $this->applicationManager->createFormByType($application);

        $type = $application->type;

        $file = $files[0] ?? null;

        $eFile = $employeeFile[0] ?? null;

        if ($file) {
            $sizeBytes = $this->fileRepository->fileSize($file);
            $fileKb = $this->fileRepository->fileSizeInKB($sizeBytes);
        }

        if ($eFile) {
            $sizeBytes = $this->fileRepository->fileSize($eFile);
            $fileKb = $this->fileRepository->fileSizeInKB($sizeBytes);
        }

        return $this->render('student/application/application-details.html.twig', [
            'application' => $application,
            'files' => $files,
            'employeeFiles' => $employeeFile,
            'form' => $form->createView(),
            'type' => $type,
            'fileSize' => $fileKb ?? null,
        ]);
    }

    /**
     * @throws Exception
     */
    #[Route('/wnioski/plik/{fileId}/usun', name: 'student_application_file_delete', methods: ['DELETE', 'POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function deleteApplicationFile(string $fileId, Request $request): JsonResponse
    {
        $file = $this->fileRepository->find($fileId);

        if (!$file) {
            throw $this->createNotFoundException($this->translator->trans('Nie znaleziono pliku'));
        }

        if (!$this->isCsrfTokenValid('delete_application_file_' . $file->id, $request->query->get('_token'))) {
            throw $this->createAccessDeniedException(
                $this->translator->trans('Nie udało się zweryfikować bezpieczeństwa żądania. Odśwież stronę i spróbuj ponownie'),
            );
        }

        $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $file->application);

        $this->fileManager->delete($file);

        return new JsonResponse(['success' => true]);
    }

    #[Route('/wnioski/plik/upload/{category2}', name: 'student_application_file_upload', methods: ['POST'])]
    #[IsGranted('ROLE_STUDENT')]
    public function uploadApplicationFile(Request $request, string $category2): JsonResponse
    {
        if (!$this->isCsrfTokenValid('upload_application_file', $request->request->get('_token'))) {
            throw $this->createAccessDeniedException();
        }

        $allowedCategories = ['decision', 'schedule', 'statement', 'files'];

        if (!in_array($category2, $allowedCategories, true)) {
            return $this->json(['error' => 'Nieprawidłowa kategoria pliku'], 400);
        }

        $application = $this->applicationManager->getApplicationFromSession();

        $application = $this->applicationRepository->find($application->id);

        if (!$application || !$application->id) {
            return $this->json(['error' => 'Brak aktywnego wniosku w sesji'], 400);
        }

        $this->denyAccessUnlessGranted(ApplicationVoter::EDIT, $application);

        $uploadedFile = $request->files->get('file');

        if (!$uploadedFile) {
            return $this->json(['error' => 'Brak pliku'], 400);
        }

        $fileConstraints = new Assert\File(
            maxSize: '10M',
            mimeTypes: ['application/pdf'],
            maxSizeMessage: 'Plik może mieć maksymalnie 10 MB',
            mimeTypesMessage: 'Obsługiwany format pliku to PDF',
        );

        $violations = $this->validator->validate($uploadedFile, $fileConstraints);

        if (count($violations) > 0) {
            return $this->json([
                'error' => $violations[0]->getMessage(),
            ], 400);
        }

        $file = new File();
        $file->application = $application;
        $file->category = ApplicationFileTypeEnum::APPLICATION_STUDENT_ATTACHMENT->value;
        $file->category2 = $category2;
        $file->file = $uploadedFile;
        $file->fileSize = $uploadedFile->getSize();
        $file->originalName = $uploadedFile->getClientOriginalName();
        $file->originalExtension = $uploadedFile->getClientOriginalExtension();

        $this->fileManager->create($file);

        return $this->json([
            'id' => $file->id,
            'name' => $file->originalName,
            'category' => $file->category2,
        ]);
    }
}

