<?php

namespace App\Controller\Student;

use App\Database\Repository\AnnouncementsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use function compact;

class AnnouncementController extends AbstractController
{
    /**
     * @param AnnouncementsRepository $announcementsRepository
     * @param PaginatorInterface $paginator
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly AnnouncementsRepository $announcementsRepository, private readonly PaginatorInterface $paginator, private readonly EntityManagerInterface $entityManager, private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Wyświetla listę aktywnych ogłoszeń dla studenta.
     *
     * Metoda prezentuje aktualne ogłoszenia oraz zapisuje informację
     * o ich obejrzeniu przez zalogowanego studenta.
     *
     * @param Request $request Żądanie HTTP zawierające numer strony
     *
     * @return Response Widok listy ogłoszeń z paginacją
     */
    #[Route('/ogloszenia', name: 'student_announcements')]
    public function index(Request $request): Response
    {
        $announcements = $this->announcementsRepository->findActive();

        $student = $this->getUser();
        $student->announcementSeen = new \DateTimeImmutable();
        $this->entityManager->persist($student);
        $this->entityManager->flush();

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($announcements, $page);

        return $this->render('student/announcements/index.html.twig', compact('announcements', 'pagination'));
    }

    /**
     * Wyświetla szczegóły ogłoszenia.
     *
     * @param string $id Identyfikator ogłoszenia
     *
     * @return Response Widok szczegółów ogłoszenia
     */
    #[Route('/ogloszenia/szczegoly/{id}', name: 'student_announcements_details')]
    public function details(string $id): Response
    {
        $announcement = $this->announcementsRepository->findOneById($id);

        if(!$announcement){
            throw $this->createNotFoundException($this->translator->trans('Nie znaleziono obiektu'));
        }

        return $this->render('student/announcements/details.html.twig', [
            'announcement' => $announcement,
        ]);
    }

}
