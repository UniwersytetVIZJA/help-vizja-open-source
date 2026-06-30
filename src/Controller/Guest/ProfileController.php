<?php

namespace App\Controller\Guest;

use App\Core\Student\StudentManager;
use App\Form\StudentProfile\UpdateDataForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileController extends AbstractController {

    public function __construct(private readonly StudentManager $studentManager, private readonly TranslatorInterface $translator){}


    #[Route('/gosc/profil/uzupełnij-dane', name: 'guest_profile_update_data')]
    #[IsGranted('ROLE_GOSC')]
    public function updateData(Request $request): Response
    {
        $studentEnity = $this->getUser();

        $form = $this->createForm(UpdateDataForm::class, $studentEnity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $this->studentManager->update($data);

            $this->addFlash('success', $this->translator->trans('Zapisano zmiany'));

            return $this->redirectToRoute('guest_dashboard');
        }

        return $this->render('guest/profile/update-data-form-2.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
