<?php

namespace App\Controller\Student;

use App\Core\Questionnaire\QuestionnaireManager;
use App\Form\Questionnaire\QuestionnaireForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

class QuestionnaireController extends AbstractController
{

    /**
     * @param QuestionnaireManager $questionnaireManager
     */
    public function __construct(private readonly QuestionnaireManager $questionnaireManager, private readonly TranslatorInterface $translator,
        #[Autowire(service: 'limiter.questionnaire_submit')]
        private readonly RateLimiterFactory $questionnaireSubmitLimiter,
    ) {}

    /**
     * Wyświetla ankietę dla studenta oraz obsługuje jej wysłanie.
     *
     * Metoda prezentuje formularz ankiety, zapisuje odpowiedzi
     * po poprawnej walidacji i przekierowuje użytkownika do profilu.
     *
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok ankiety lub przekierowanie po jej wysłaniu
     */
    #[Route('/ankieta', name: 'student_questionnaire')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(QuestionnaireForm::class);
        $form->handleRequest($request);

        if ($request->isMethod('POST')) {
            $limiterKey = $request->getClientIp() ?? 'unknown';
            $limit = $this->questionnaireSubmitLimiter
                ->create($limiterKey)
                ->consume(1);

            if (!$limit->isAccepted()) {
                $this->addFlash(
                    'danger',
                    $this->translator->trans('Ankietę można wysłać tylko raz na 24 godziny.')
                );

                return $this->redirectToRoute('student_profile_index');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $this->questionnaireManager->createQuestionnaire($data);

            $this->addFlash('success', $this->translator->trans('Ankieta została przesłana prawidłowo'));

            return $this->redirectToRoute('student_profile_index');
        }

        return $this->render('student/questionnaire/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
