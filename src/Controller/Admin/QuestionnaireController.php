<?php

namespace App\Controller\Admin;

use App\Database\Repository\QuestionnaireRepository;
use App\Form\Questionnaire\QuestionnaireForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use function count;

class QuestionnaireController extends AbstractController
{
    /**
     * @param QuestionnaireRepository $questionnaireRepository
     */
    public function __construct(private readonly QuestionnaireRepository $questionnaireRepository) {}

    /**
     * Wyświetla wyniki ankiety w panelu administracyjnym.
     *
     * Metoda prezentuje średnie wartości odpowiedzi dla wybranych pytań
     * ankiety oraz ich etykiety, a także listę wszystkich wypełnionych ankiet.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @return Response Widok wyników ankiety
     */
    #[Route('/admin/wyniki-ankiety', name: 'admin_questionnaire')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function index(): Response
    {
        $fields = ['q1', 'q2', 'q3', 'q4', 'q4', 'q5', 'q7', 'q9', 'q10', 'q11'];

        $questionnaire = $this->questionnaireRepository->findAll();

        $count = count($questionnaire);

        foreach ($fields as $field) {
            $averages[$field] = $this->questionnaireRepository->getAverage($field);
        }

        $form = $this->createForm(QuestionnaireForm::class);
        $formView = $form->createView();
        $labels = [];
        foreach ($fields as $field) {
            $labels[$field] = $formView[$field]->vars['label'] ?? $field;
        }

        return $this->render('admin/questionnaire/index.html.twig', [
            'averages' => $averages,
            'labels' => $labels,
            'form' => $formView,
            'questionnaire' => $questionnaire,
            'count' => $count,
        ]);
    }
}
