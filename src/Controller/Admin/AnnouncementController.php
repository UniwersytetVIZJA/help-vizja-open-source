<?php

namespace App\Controller\Admin;

use App\Core\Announcement\AnnouncementManager;
use App\Database\Entity\Announcements;
use App\Database\Repository\AnnouncementsRepository;
use App\Form\Announcement\CreateAnnouncementForm;
use App\Verbis\VerbisService;
use Doctrine\ORM\EntityManagerInterface;
use Exercise\HTMLPurifierBundle\HTMLPurifiersRegistryInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use function compact;
use function sprintf;

class AnnouncementController extends AbstractController
{
    public function __construct(
        private readonly AnnouncementsRepository $announcementsRepository, private readonly AnnouncementManager $announcementManager,
        private readonly HTMLPurifiersRegistryInterface $purifier, private readonly EntityManagerInterface $entityManager, private readonly PaginatorInterface $paginator, private readonly VerbisService $verbisService, private readonly TranslatorInterface $translator
    ) {}

    /**
     * Wyświetla listę aktywnych ogłoszeń w panelu administracyjnym.
     *
     * @param Request $request Żądanie HTTP zawierające m.in. numer strony
     *
     * @return Response Widok listy ogłoszeń z paginacją
     */
    #[Route('/admin/ogloszenia', name: 'admin_announcement')]
    public function index(Request $request): Response
    {
        $announcements = $this->announcementsRepository->findActive();

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($announcements, $page);

        return $this->render('admin/announcements/index.html.twig', compact('announcements', 'pagination'));
    }

    /**
     * Wyświetla formularz tworzenia ogłoszenia oraz obsługuje jego zapis.
     *
     * Metoda inicjalizuje formularz dodawania ogłoszenia, waliduje dane
     * przesłane przez użytkownika i zapisuje nowe ogłoszenie w systemie.
     * W przypadku powodzenia przekierowuje do listy ogłoszeń.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza lub przekierowanie po zapisie
     */
    #[Route('/admin/ogloszenia/dodaj-ogloszenie', name: 'admin_create_announcement')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function create(Request $request): Response
    {
        $announcement = new Announcements();
        $form = $this->createForm(CreateAnnouncementForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $announcement = $form->getData();
                $this->announcementManager->createAnnouncement($announcement);

                $this->addFlash('success', $this->translator->trans('Ogłoszenie zostało pomyślnie dodane'));

                return $this->redirectToRoute('admin_announcement');
            }

            $this->addFlash('error', $this->translator->trans('Formularz zawiera błędy. Proszę poprawić oznaczone pola'));
        }

        return $this->render('admin/announcements/create-announcement.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Wyświetla formularz edycji ogłoszenia oraz obsługuje jego aktualizację.
     *
     * Metoda pobiera ogłoszenie na podstawie identyfikatora, umożliwia
     * edycję jego danych oraz zapis zmian. W przypadku braku ogłoszenia
     * zgłaszany jest wyjątek 404.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param string $id Identyfikator edytowanego ogłoszenia
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza edycji lub przekierowanie po zapisie
     */
    #[Route('/admin/ogloszenia/edytuj/{id}', name: 'admin_update_announcement', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function update(string $id, Request $request): Response
    {
        $announcement = $this->announcementsRepository->find($id);

        if (!$announcement) {
            throw $this->createNotFoundException(sprintf('Nie znaleziono ogłoszenia ID: %s', $id));
        }

        $form = $this->createForm(CreateAnnouncementForm::class, $announcement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $announcement = $form->getData();
            $this->announcementManager->createAnnouncement($announcement);

            return $this->redirectToRoute('admin_announcement');
        }

        return $this->render('admin/announcements/update.html.twig', [
            'announcement' => $announcement,
            'form' => $form->createView(),
            'errors' => $form->isSubmitted() && !$form->isValid()
        ]);
    }

    /**
     * Wyświetla szczegóły wybranego ogłoszenia.
     *
     * @param string $id Identyfikator ogłoszenia
     *
     * @return Response Widok szczegółów ogłoszenia
     */
    #[Route('/admin/ogloszenia/szczegoly/{id}', name: 'admin_announcements_details')]
    public function details(string $id): Response
    {
        $announcement = $this->announcementsRepository->findOneById($id);

        if(!$announcement){
            throw $this->createNotFoundException($this->translator->trans('Nie znaleziono obiektu'));
        }

        return $this->render('admin/announcements/details.html.twig', [
            'announcement' => $announcement,
        ]);
    }

    /**
     * Wyświetla archiwalne ogłoszenia w panelu administracyjnym.
     *
     * @param Request $request Żądanie HTTP zawierające m.in. numer strony
     *
     * @return Response Widok archiwum ogłoszeń z paginacją
     */
    #[Route('/admin/ogloszenia/archiwum', name: 'admin_announcements_archive')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function archive(Request $request): Response
    {
        $announcements = $this->announcementsRepository->findArchive();

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($announcements, $page);

        return $this->render('admin/announcements/archive.html.twig', compact('announcements', 'pagination'));
    }

}
