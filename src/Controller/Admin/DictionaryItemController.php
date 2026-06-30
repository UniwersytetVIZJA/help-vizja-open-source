<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Dictionary\DictionaryManager;
use App\Core\DictionaryItem\DictionaryItemManager;
use App\Database\Entity\Dictionary\Item;
use App\Form\Dictionary\ItemForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DictionaryItemController
 * @package App\Controller\Admin
 */
class DictionaryItemController extends AbstractController
{
    /**
     * DictionaryItemController constructor
     * @param DictionaryManager $dictionaryManager
     * @param DictionaryItemManager $dictionaryItemManager
     */
    public function __construct(
        private readonly DictionaryManager $dictionaryManager,
        private readonly DictionaryItemManager $dictionaryItemManager, private readonly TranslatorInterface $translator,
    ) {}

    /**
     * Wyświetla formularz dodawania nowej wartości do słownika oraz obsługuje jej zapis.
     *
     * Metoda tworzy nowy element słownika, waliduje dane formularza
     * i zapisuje je w systemie. Po powodzeniu przekierowuje do listy
     * wartości danego słownika.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_ADMIN.
     *
     * @param string $dictionaryId Identyfikator słownika
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza lub przekierowanie po zapisie
     */
    #[Route('/admin/slowniki/{dictionaryId}/wartosc/dodaj', name: 'admin_create_dictionary_item')]
    #[IsGranted('ROLE_ADMIN')]
    public function create(string $dictionaryId, Request $request): Response
    {
        $dictionary = $this->dictionaryManager->findOneById($dictionaryId);
        $item = new Item();

        $form = $this->createForm(ItemForm::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item = $form->getData();

            $this->dictionaryItemManager->create($item, $dictionary);
            $this->addFlash('success', $this->translator->trans('Dodano nowy element'));

            return $this->redirectToRoute('admin_dictionary_items', [
                'dictionaryId' => $dictionary->id,
            ]);
        }

        return $this->render('admin/dictionary-item/create.html.twig', [
            'dictionary' => $dictionary,
            'form' => $form->createView(),
            'item' => $item,
        ]);
    }

    /**
     * Wyświetla formularz edycji wartości słownika oraz obsługuje zapis zmian.
     *
     * Metoda umożliwia modyfikację istniejącego elementu słownika,
     * waliduje dane formularza i zapisuje zmiany w systemie.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_ADMIN.
     *
     * @param string $dictionaryItemId Identyfikator edytowanego elementu słownika
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza edycji lub przekierowanie po zapisie
     */
    #[Route('/admin/slowniki/wartosci/{dictionaryItemId}/edytuj', name: 'admin_update_dictionary_item')]
    #[IsGranted('ROLE_ADMIN')]
    public function update(string $dictionaryItemId, Request $request): Response
    {
        $item = $this->dictionaryItemManager->findOneById($dictionaryItemId);

        $form = $this->createForm(ItemForm::class, $item);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $item = $form->getData();

            $this->dictionaryItemManager->update($item);
            $this->addFlash('success', $this->translator->trans('Zmieniono element'));

            return $this->redirectToRoute('admin_dictionary_items', [
                'dictionaryId' => $item->dictionary->id,
            ]);
        }

        return $this->render('admin/dictionary-item/update.html.twig', [
            'dictionary' => $item->dictionary,
            'form' => $form->createView(),
            'item' => $item,
        ]);
    }
}
