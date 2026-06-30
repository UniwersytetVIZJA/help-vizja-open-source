<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\User\UserManager;
use App\Core\User\UserRepository;
use App\Database\Entity\User;
use App\Form\User\CreateUserForm;
use App\Mailer\Mail\User\CreateUser;
use App\Mailer\MailerService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use function sprintf;

class UserController extends AbstractController
{
    /**
     * @param UserManager $userManager
     * @param PaginatorInterface $paginator
     * @param MailerService $mailerService
     * @param UserRepository $userRepository
     */
    public function __construct(private readonly UserManager $userManager,
        private readonly PaginatorInterface $paginator, private readonly MailerService $mailerService,
        private readonly UserRepository $userRepository, private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Wyświetla listę użytkowników w panelu administracyjnym.
     *
     * Umożliwia filtrowanie użytkowników na podstawie danych przekazanych
     * w parametrach zapytania oraz prezentuje wyniki z paginacją.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_ADMIN.
     *
     * @param Request $request Żądanie HTTP zawierające parametry filtrowania i numer strony
     *
     * @return Response Widok listy użytkowników z paginacją
     */
    #[Route('/admin/uzytkownicy', name: 'admin_users')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(Request $request): Response
    {
        $this->userRepository->findAll();

        $user = $request->query->get('user', '');
        $query = $this->userRepository->findFilter($user);

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($query, $page);

        return $this->render('admin/users/users.html.twig', [
            'users' => $pagination,
            'pagination' => $pagination,
            'filter' => [
                'user' => $user,
            ],
        ]);
    }

    /**
     * Usuwa użytkownika z systemu.
     *
     * Metoda usuwa użytkownika na podstawie identyfikatora.
     * W przypadku braku użytkownika wyświetlany jest komunikat błędu.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_ADMIN.
     *
     * @param string $id Identyfikator użytkownika
     * @param EntityManagerInterface $em Menedżer encji
     *
     * @return Response Przekierowanie do listy użytkowników
     */
    #[Route('/admin/uzytkownicy/usun/{id}', name: 'admin_user_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(string $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);

        if (!$user) {
            $this->addFlash('error', $this->translator->trans('Użytkownik nie istnieje'));

            return $this->redirectToRoute('admin_users');
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', $this->translator->trans('Użytkownik został usunięty'));

        return $this->redirectToRoute('admin_users');
    }

    /**
     * Wyświetla formularz edycji użytkownika oraz obsługuje zapis zmian.
     *
     * Metoda umożliwia modyfikację danych użytkownika,
     * waliduje dane formularza i zapisuje zmiany w systemie.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_ADMIN.
     *
     * @param string $id Identyfikator edytowanego użytkownika
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza edycji lub przekierowanie po zapisie
     */
    #[Route('/admin/uzytkownicy/edytuj/{id}', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(string $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException(sprintf('Nie znaleziono użytkownika o ID: %s', $id));
        }

        $form = $this->createForm(CreateUserForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $this->userManager->createUser($user);

            $this->addFlash('success', $this->translator->trans('Użytkownik zaktualizowany'));

            return $this->redirectToRoute('admin_users');
        }

        return $this->render('admin/users/edit_user.html.twig', [
            'user' => $user,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Wyświetla formularz tworzenia nowego użytkownika oraz obsługuje jego zapis.
     *
     * Metoda tworzy nowego użytkownika w systemie, weryfikuje unikalność
     * adresu e-mail oraz wysyła wiadomość informacyjną do nowego użytkownika.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_ADMIN.
     *
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza lub przekierowanie po utworzeniu użytkownika
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    #[Route('/admin/uzytkownicy/dodaj', name: 'admin_users_create')]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request): Response
    {
        $form = $this->createForm(CreateUserForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $form->getData();
            $email = $user->email;
            $existing = $this->userRepository->findOneBy(['email' => $email]);

            if ($existing) {
                $form->get('email')->addError(new FormError('Użytkownik o podanym adresie mailowym już istnieje'));
            } else {
                $this->userManager->createUser($user);

                $mailContent = CreateUser::fromEntity($user);
                $this->mailerService->sendEmailToEmployee($user, $mailContent);

                $this->addFlash('success', $this->translator->trans('Użytkownik został utworzony'));

                return $this->redirectToRoute('admin_users');
            }
        }

        return $this->render('admin/users/create-user.html.twig', [
            'form' => $form->createView(),
        ]);
    }

}
