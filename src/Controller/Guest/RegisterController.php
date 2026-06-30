<?php

namespace App\Controller\Guest;

use App\Core\Student\StudentManager;
use App\Form\RegisterAccount\CreateAccountForm;
use App\Mailer\Mail\Account\Create;
use App\Mailer\MailerService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class RegisterController extends AbstractController
{
    public function __construct(private readonly StudentManager     $studentManager, private readonly MailerService $mailerService, private readonly TranslatorInterface $translator,
                                #[Autowire(service: 'limiter.guest_create_account')]
                                private readonly RateLimiterFactory $rateLimiterFactory,
    )
    {
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    #[Route('/gosc/zarejestruj', name: 'guest_register')]
    public function index(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $limiter = $this->rateLimiterFactory->create(
                $request->getClientIp() ?? 'unknown',
            );

            $limit = $limiter->consume(1);

            if (!$limit->isAccepted()) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans(
                        'Zbyt wiele prób utworzenia konta. Spróbuj ponownie za kilka minut.',
                    ),
                );

                return $this->redirectToRoute('guest_register');
            }
        }

        $form = $this->createForm(CreateAccountForm::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $firstName = $form->get('firstName')->getData();
            $lastName = $form->get('lastName')->getData();
            $email = $form->get('email')->getData();
            $password = $form->get('password')->getData();

            try {
                $student = $this->studentManager->createGuest(
                    $firstName,
                    $lastName,
                    $email,
                    $password,
                );
            } catch (RuntimeException $e) {
                $form->get('email')->addError(
                    new FormError($this->translator->trans('Nie można utworzyć konta dla podanego adresu e-mail.')),
                );

                return $this->render('guest/register/create-account.html.twig', [
                    'form' => $form->createView(),
                ]);
            }

            $mailContent = Create::fromEntity($student);
            $this->mailerService->sendEmailToStudent($student, $mailContent);

            $this->addFlash(
                'success',
                $this->translator->trans('Konto zostało pomyślnie utworzone'),
            );

            return $this->redirectToRoute('student_login');
        }

        return $this->render('guest/register/create-account.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
