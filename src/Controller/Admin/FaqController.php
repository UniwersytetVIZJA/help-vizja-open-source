<?php

namespace App\Controller\Admin;

use App\Core\Faq\FaqManager;
use App\Database\Entity\Faq;
use App\Database\Repository\FaqRepository;
use App\Form\FAQ\FaqForm;
use App\Form\LanguageEnum;
use Doctrine\ORM\Exception\ORMException;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use function sprintf;

class FaqController extends AbstractController
{
    public function __construct(private readonly FaqRepository $faqRepository, private readonly FaqManager $faqManager, private readonly PaginatorInterface $paginator) {}

    #[Route('/admin/faq', name: 'admin_faq')]
    public function index(Request $request): Response
    {
        $language = $request->query->get('language');
        $question = $request->query->get('question');

        $faq = $this->faqRepository->findFilter($language, $question);

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($faq, $page);

        return $this->render('admin/faq/faq.html.twig', [
            'faq' => $faq,
            'pagination' => $pagination,
            'faqEnum' => LanguageEnum::cases(),
            'filters' => [
                'language' => $language,
                'question' => $question,
            ],
        ]);
    }

    #[Route('/admin/faq/edytuj/{id}', name: 'admin_faq_update')]
    public function updateFaq(Request $request, Faq $id): Response
    {
        $faq = $this->faqRepository->find($id);

        if (!$faq) {
            throw $this->createNotFoundException(sprintf('Nie znaleziono pytania ID: %s', $id->id));
        }

        $form = $this->createForm(FaqForm::class, $faq);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newFaq = $form->getData();
            $this->faqManager->update($newFaq);

            return $this->redirectToRoute('admin_faq');
        }

        return $this->render('admin/faq/faq-form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/faq/utworz/', name: 'admin_faq_create')]
    public function createFaq(Request $request): Response
    {
        $form = $this->createForm(FaqForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $faq = $form->getData();
            $this->faqManager->create($faq);

            return $this->redirectToRoute('admin_faq');
        }

        return $this->render('admin/faq/faq-form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @throws ORMException
     */
    #[Route('/admin/faq/usun/{id}', name: 'admin_faq_delete')]
    public function deleteFaq(Request $request, Faq $id): Response
    {
        $faq = $this->faqRepository->find($id);

        $this->faqManager->deleteFaq($faq);

        if (!$faq) {
            throw $this->createNotFoundException('Pytanie nie istnieje');
        }

        return $this->redirectToRoute('admin_faq');
    }
}
