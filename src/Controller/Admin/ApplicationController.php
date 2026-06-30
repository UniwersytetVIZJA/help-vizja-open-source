<?php

namespace App\Controller\Admin;

use App\Application\Enum\ApplicationType;
use App\Application\Strategy\ApplicationStrategyManager;
use App\Core\Application\ApplicationManager;
use App\Core\Application\ApplicationRepository;
use App\Core\Application\Form\Type\SpecialisedEquipment;
use App\Core\DictionaryItem\DictionaryItemManager;
use App\Core\File\FileManager;
use App\Core\File\FileUploader;
use App\Core\Student\StudentRepository;
use App\Database\Entity\Application\EducationalProcess;
use App\Database\Entity\Application\LanguageInterpreter;
use App\Database\Entity\Application\TeachingAssistant;
use App\Database\Entity\File;
use App\Database\Repository\Application\EducationalProcessRepository;
use App\Database\Repository\Application\LanguageInterpreterRepository;
use App\Database\Repository\Application\SpecialisedEquipmentRepository;
use App\Database\Repository\ApplicationFinder;
use App\Database\Repository\FileRepository;
use App\Form\Application\ApplicationAdaptationCardForm;
use App\Form\Application\ApplicationEmployeeCommentForm;
use App\Form\Application\ApplicationStatusEditForm;
use App\Form\ApplicationFileTypeEnum;
use App\Mailer\Mail\Application\EmployeeComment;
use App\Mailer\Mail\Application\StatusChange;
use App\Mailer\MailerService;
use App\Service\DocxGenerator;
use App\Service\PDFGenerator;
use Form\Application\AdminTypeEdit;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpWord\Exception\CopyFileException;
use PhpOffice\PhpWord\Exception\CreateTemporaryFileException;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;
use setasign\Fpdi\PdfParser\Filter\FilterException;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use setasign\Fpdi\PdfReader\PdfReaderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

class ApplicationController extends AbstractController
{
    /**
     * @param ApplicationManager $applicationManager
     * @param DictionaryItemManager $dictionaryItemManager
     * @param ApplicationRepository $applicationRepository
     * @param FileManager $fileManager
     * @param FileRepository $fileRepository
     * @param StudentRepository $studentRepository
     * @param FileUploader $fileUploader
     * @param ApplicationFinder $finder
     * @param EducationalProcessRepository $educationalProcessRepository
     * @param LanguageInterpreterRepository $languageInterpreterRepository
     * @param SpecialisedEquipmentRepository $specialisedEquipmentRepository
     * @param SpecialisedEquipment $specialisedEquipment
     * @param MailerService $mailerService
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
        private readonly EducationalProcessRepository $educationalProcessRepository,
        private readonly LanguageInterpreterRepository $languageInterpreterRepository,
        private readonly SpecialisedEquipmentRepository $specialisedEquipmentRepository, private readonly SpecialisedEquipment $specialisedEquipment,
        private readonly MailerService $mailerService, private readonly PaginatorInterface $paginator,
        private readonly DocxGenerator $docxGenerator,
        private readonly PDFGenerator $PDFGenerator, private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Wyświetla listę wniosków w panelu administracyjnym z filtrowaniem i paginacją.
     *
     * Obsługuje filtry przekazane w query string (type, student, status, from, to),
     * gdzie zakres dat jest parsowany do DateTimeImmutable.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Request $request Żądanie HTTP zawierające parametry filtrowania i numer strony
     *
     * @return Response Widok listy wniosków wraz z paginacją i aktywnymi filtrami
     *
     * @throws \DateMalformedStringException Gdy przekazany format daty (from/to) jest nieprawidłowy
     */
    #[Route('/admin/wnioski/', name: 'admin_applications')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function showAll(Request $request): Response
    {
        $name = 'wszystkie';

        $type = $request->query->get('type');
        $student = $request->query->get('student');
        $status = $request->query->get('status');
        $fromParam = $request->query->get('from') ? new \DateTimeImmutable($request->query->get('from')) : null;
        $toParam = $request->query->get('to') ? new \DateTimeImmutable($request->query->get('to') . ' 23:59:59') : null;

        $applications = $this->applicationRepository->findFilter($type, $student, $status, $fromParam, $toParam);
        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($applications, $page);

        return $this->render('admin/applications/index.html.twig', [
            'applications' => $pagination,
            'application_name' => $name,
            'pagination' => $pagination,
            'filters' => [
                'type' => $type,
                'student' => $student,
                'status' => $status,
                'from' => $fromParam,
                'to' => $toParam,
            ],
        ]);
    }

    /**
     * Wyświetla szczegóły wniosku oraz umożliwia jego aktualizację przez pracownika.
     *
     * Metoda prezentuje dane wniosku, powiązane pliki oraz obsługuje formularze
     * zmiany statusu i dodania komentarza pracownika. Po zapisie wysyłane są
     * odpowiednie powiadomienia e-mail do studenta.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param string $applicationId Identyfikator wniosku
     * @param Request $request Żądanie HTTP zawierające dane formularzy
     *
     * @return Response Widok szczegółów wniosku lub przekierowanie po zapisie
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    #[Route('/admin/wniosek/szczegoly/{applicationId}', name: 'admin_applications_applicationDetails')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function viewApplication(string $applicationId, Request $request): Response
    {
        $application = $this->applicationRepository->findOneById($applicationId);

        if (!$application) {
            throw $this->createNotFoundException('Wniosek nie istnieje');
        }

        $files = $this->fileRepository->findBy([
            'application' => $application,
            'category' => ApplicationFileTypeEnum::APPLICATION_STUDENT_ATTACHMENT->value
        ]);

        $employeeFile = $this->fileRepository->findBy([
            'application' => $application,
            'category' => ApplicationFileTypeEnum::APPLICATION_EMPLOYEE_ATTACHMENT->value
        ]);

        $eFile = $employeeFile[0] ?? null;

        $file = $files[0] ?? null;

        if ($file) {
            $sizeBytes = $this->fileRepository->fileSize($file);
            $fileKb = $this->fileRepository->fileSizeInKB($sizeBytes);
        }

        if ($eFile) {
            $sizeBytes = $this->fileRepository->fileSize($eFile);
            $fileKb = $this->fileRepository->fileSizeInKB($sizeBytes);
        }

        $type = $application->type;

        $formComment = $this->createForm(ApplicationEmployeeCommentForm::class, $application);
        $formStatus = $this->createForm(ApplicationStatusEditForm::class, $application);
        $formAdaptationCard = $this->createForm(ApplicationAdaptationCardForm::class, $application, ['category' => $file->category ?? null]);
        $formAdaptationCard->handleRequest($request);
        $formComment->handleRequest($request);
        $formStatus->handleRequest($request);

        $student = $application->student;

        $isEducationalProcess = $application instanceof EducationalProcess;

        if ($formComment->isSubmitted() && $formComment->isValid()) {
            $application->employeeCommentDate = new \DateTimeImmutable();
            $this->applicationManager->update($application);
            $this->addFlash('success', $this->translator->trans('Wniosek został zaktualizowany'));

            $mailContent = EmployeeComment::fromEntity($student, $application);
            $this->mailerService->sendEmailToStudent($student, $mailContent);

            return $this->redirectToRoute('admin_applications_applicationDetails', ['applicationId' => $applicationId]);
        }

        if ($formStatus->isSubmitted() && $formStatus->isValid()) {
            $this->applicationManager->update($application);
            $this->addFlash('success', $this->translator->trans('Wniosek został zaktualizowany'));

            $mailContent = StatusChange::fromEntity($student, $application);
            $this->mailerService->sendEmailToStudent($student, $mailContent);

            return $this->redirectToRoute('admin_applications_applicationDetails', ['applicationId' => $applicationId]);
        }

        if ($formAdaptationCard->isSubmitted() && $formAdaptationCard->isValid()) {
            $uploadedFiles = $this->fileUploader->getFiles($formAdaptationCard);

            foreach ($uploadedFiles as $fileEntity) {
                if (!$fileEntity instanceof File) {
                    continue;
                }
                $fileEntity->application = $application;
                $fileEntity->category = ApplicationFileTypeEnum::APPLICATION_EMPLOYEE_ATTACHMENT->value;
                $this->fileManager->create($fileEntity);
            }

            $this->applicationManager->update($application);
            $this->addFlash('success', $this->translator->trans('Wniosek został zaktualizowany'));

            return $this->redirectToRoute('admin_applications_applicationDetails', ['applicationId' => $applicationId]);
        }

        return $this->render('admin/applications/application-details.html.twig', [
            'formStatus' => $formStatus->createView(),
            'formComment' => $formComment->createView(),
            'formAdaptationCard' => $formAdaptationCard->createView(),
            'application' => $application,
            'files' => $files,
            'employeeFile' => $employeeFile,
            'type' => $type,
            'fileSize' => $fileKb ?? null,
            'isEducationalProcess' => $isEducationalProcess,
        ]);
    }

    /**
     * Wyświetla plik powiązany z wnioskiem w przeglądarce.
     *
     * Metoda pobiera plik na podstawie identyfikatora i zwraca go
     * do wyświetlenia online (bez wymuszania pobrania).
     *
     * @param string $fileId Identyfikator pliku
     *
     * @return BinaryFileResponse Odpowiedź z plikiem
     */
    #[Route('/admin/wniosek/wyswietl-plik/{fileId}', name: 'admin_application_file_show')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function showFile(string $fileId): BinaryFileResponse
    {
        $file = $this->fileRepository->find($fileId);

        if (!$file) {
            throw $this->createNotFoundException('Plik nie istnieje');
        }

        $filePath = $this->fileManager->getFilePath($file);

        return $this->file(
            $filePath,
            $file->fileName,
            ResponseHeaderBag::DISPOSITION_INLINE,
        );
    }

    /**
     * Umożliwia pobranie pliku powiązanego z wnioskiem.
     *
     * Metoda pobiera plik na podstawie identyfikatora i zwraca go
     * jako załącznik, wymuszając jego pobranie przez przeglądarkę.
     *
     * @param string $fileId Identyfikator pliku
     *
     * @return BinaryFileResponse Odpowiedź z plikiem do pobrania
     */
    #[Route('/admin/wniosek/pobierz-plik/{fileId}', name: 'admin_application_file_download')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function downloadFile(string $fileId): BinaryFileResponse
    {
        $file = $this->fileRepository->find($fileId);

        if (!$file) {
            throw $this->createNotFoundException('Plik nie istnieje');
        }

        $filePath = $this->fileManager->getFilePath($file);

        return $this->file(
            $filePath,
            $file->originalName,
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        );
    }

    /**
     * Wyszukuje wnioski na podstawie wybranych filtrów.
     *
     * Obsługuje parametry filtrowania przekazane w query string
     * (type, student, status, from, to) oraz zwraca wyniki
     * z paginacją w widoku listy wniosków.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Request $request Żądanie HTTP zawierające parametry wyszukiwania
     *
     * @return Response Widok listy wniosków z wynikami wyszukiwania
     *
     * @throws \DateMalformedStringException Gdy przekazany format daty (from/to) jest nieprawidłowy
     */
    #[Route('/admin/wnioski/szukaj', name: 'admin_applications_search')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function search(Request $request): Response
    {
        $application = $this->applicationRepository->findAllNotNull();

        $type = $request->query->get('type');
        $student = $request->query->get('student');
        $status = $request->query->get('status');
        $fromParam = $request->query->get('from') ? new \DateTimeImmutable($request->query->get('from')) : null;
        $toParam = $request->query->get('to') ? new \DateTimeImmutable($request->query->get('to') . ' 23:59:59') : null;

        $applications = $this->applicationRepository->findFilter($type, $student, $status, $fromParam, $toParam);

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($application, $page);

        return $this->render('admin/applications/index.html.twig', [
            'applications' => $applications,
            'pagination' => $pagination,
            'filters' => [
                'type' => $type,
                'student' => $student,
                'status' => $status,
                'from' => $fromParam,
                'to' => $toParam,
            ],
        ]);
    }

    /**
     * @param string $applicationId
     * @return BinaryFileResponse
     * @throws \PhpOffice\PhśpWord\Exception\CopyFileException
     * @throws CreateTemporaryFileException
     * @throws CopyFileException
     */
    #[Route('/admin/wniosek/szczegoly/{applicationId}/generuj-docx', name: 'admin_docx_generate')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function docx(string $applicationId): BinaryFileResponse
    {
        $application = $this->applicationRepository->findOneById($applicationId);

        if (!$application) {
            throw $this->createNotFoundException('Wniosek nie istnieje');
        }

        $template = $this->getParameter('kernel.project_dir') . '/templates/docx/adaptation_card.docx';

        $adaptationRows = [];

        if ($application instanceof EducationalProcess) {
            foreach ($application->adaptations as $adaptation) {
                $adaptationRows[] = [
                    'LISTA_ADAPTACJI' => $adaptation->value,
                ];
            }
        }

        $file = $this->docxGenerator->generate(
            $template,
            [
                'IMIE' => $application->student->firstName,
                'NAZWISKO' => $application->student->lastName,
                'NR_ALBUMU' => $application->albumNumber,
                'WYDZIAL' => $application->faculty,
                'ROK_STUDIOW' => $application->year,
                'SEMESTR_STUDIOW' => $application->semester,
                'TRYB_STUDIOW' => match ($application->studyMode) {
                    'Z' => 'Zaocznie',
                    'S' => 'Stacjonarnie',
                    default => '',
                },
                'CZAS_TRWANIA_ADAPTACJI' => $application->adaptationCard->value ?? '',
                'INNE_ADAPTACJE' => $application->adaptation_another
                    ? $this->translator->trans('Inne') . ': ' . $application->adaptation_another
                    : '',
                'ROK_AKADEMICKI' => '2025/2026',
                'DATA_WYDANIA_ADAPTACJI' => $application->adaptationCardIssueDate
                    ? $application->adaptationCardIssueDate->format('d.m.Y')
                    : '',
            ],
            [
                'LISTA_SEMANTYCZNA' => $adaptationRows,
            ]
        );

        $resp = new BinaryFileResponse($file);
        $resp->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'Karta-adaptacji-dostępna-cyfrowo.docx');

        $resp->deleteFileAfterSend(true);

        return $resp;
    }

    /**
     * @throws CrossReferenceException
     * @throws PdfReaderException
     * @throws PdfParserException
     * @throws PdfTypeException
     * @throws FilterException
     */
    #[Route('/admin/wniosek/szczegoly/{applicationId}/generuj-pdf', name: 'admin_pdf_generate')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function pdf(string $applicationId): BinaryFileResponse
    {
        $application = $this->applicationRepository->findOneById($applicationId);

        if (!$application) {
            throw $this->createNotFoundException('Wniosek nie istnieje');
        }

        $template = $this->getParameter('kernel.project_dir') . '/templates/pdf/adaptation_card.pdf';

        $file = $this->PDFGenerator->generate($template, [
            ['page' => 1, 'x' => 60, 'y' => 100, 'value' => $application->student->firstName . ' ' . $application->student->lastName],
        ]);
        $resp = new BinaryFileResponse($file);
        $resp->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'Karta-adaptacji-dostępna-cyfrowo.pdf');
        $resp->deleteFileAfterSend(true);

        return $resp;
    }

    #[Route('/admin/wniosek/edytuj/{applicationId}', name: 'admin_application_update')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateApplication(string $applicationId, Request $request): Response
    {
        $application = $this->applicationRepository->findOneById($applicationId);

        if (!$application) {
            throw $this->createNotFoundException('Wniosek nie istnieje');
        }

        $form = $this->applicationManager->createFormByType($application);

        if ($application instanceof EducationalProcess || $application instanceof TeachingAssistant || $application instanceof LanguageInterpreter) {
            $form->remove('agreements');
            $form->remove('statute');
            $form->remove('agreementDean');
            $form->remove('agreementLecturers');
        }

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $uploadedFiles = $this->fileUploader->getFiles($form);

            foreach ($uploadedFiles as $fileEntity) {
                if (!$fileEntity instanceof File) {
                    continue;
                }
                $fileEntity->application = $application;
                $fileEntity->category = ApplicationFileTypeEnum::APPLICATION_STUDENT_ATTACHMENT->value;
                $this->fileManager->create($fileEntity);
            }
            $this->applicationManager->update($application);

            return $this->redirectToRoute('admin_applications_applicationDetails', ['applicationId' => $applicationId]);
        }

        return $this->render('admin/applications/application-update.html.twig', [
            'application' => $application,
            'form' => $form->createView(),
        ]);
    }
}
