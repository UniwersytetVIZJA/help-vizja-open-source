<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Core\Dictionary\DictionaryManager;
use App\Core\Dictionary\DictionaryRepository;
use App\Core\DictionaryItem\DictionaryItemManager;
use App\Core\DictionaryItem\DictionaryItemRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Class DictionaryController
 * @package App\Controller\Admin
 */
class DictionaryController extends AbstractController
{
    /**
     * DictionaryController constructor
     * @param DictionaryManager $dictionaryManager
     * @param DictionaryItemManager $dictionaryItemManager
     * @param DictionaryRepository $dictionaryRepository
     * @param DictionaryItemRepository $dictionaryItemRepository
     */
    public function __construct(
        private readonly DictionaryManager $dictionaryManager,
        private readonly DictionaryItemManager $dictionaryItemManager, private readonly DictionaryRepository $dictionaryRepository, private readonly DictionaryItemRepository $dictionaryItemRepository,
    ) {}

    /**
     * Wyświetla listę słowników w panelu administracyjnym.
     *
     * Umożliwia filtrowanie słowników na podstawie nazwy oraz
     * prezentuje wyniki z paginacją.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_ADMIN.
     *
     * @param PaginatorInterface $paginator Serwis paginacji wyników
     * @param Request $request Żądanie HTTP zawierające parametry filtrowania
     *
     * @return Response Widok listy słowników z paginacją
     */
    #[Route('/admin/slowniki', name: 'admin_dictionaries')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(PaginatorInterface $paginator, Request $request): Response
    {
        $this->dictionaryManager->findAll();

        $dictionaries = $request->query->get('dictionary', '');
        $query = $this->dictionaryRepository->findFilter($dictionaries);

        $page = $request->query->getInt('page', 1);
        $pagination = $paginator->paginate($query, $page);

        return $this->render('admin/dictionary/index.html.twig', [
            'dictionaries' => $pagination,
            'pagination' => $pagination,
            'filter' => [
                'dictionary' => $dictionaries,
            ],
        ]);
    }

    /**
     * Wyświetla listę wartości wybranego słownika w panelu administracyjnym.
     *
     * Umożliwia filtrowanie pozycji słownika oraz prezentuje
     * wyniki z paginacją.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_ADMIN.
     *
     * @param string $dictionaryId Identyfikator słownika
     * @param PaginatorInterface $paginator Serwis paginacji wyników
     * @param Request $request Żądanie HTTP zawierające parametry filtrowania
     *
     * @return Response Widok listy wartości słownika z paginacją
     */
    #[Route('/admin/slowniki/{dictionaryId}/wartosci', name: 'admin_dictionary_items', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function items(string $dictionaryId, PaginatorInterface $paginator, Request $request): Response
    {
        $dictionary = $this->dictionaryManager->findOneById($dictionaryId);
        $this->dictionaryItemManager->findAllByDictionaryId($dictionary->id);

        $items = $request->get('item', '');
        $query = $this->dictionaryItemRepository->findFilterItem($dictionaryId, $items);

        $page = $request->query->getInt('page', 1);
        $pagination = $paginator->paginate($query, $page);

        return $this->render('admin/dictionary/items.html.twig', [
            'dictionary' => $dictionary,
            'dictionaryId' => $dictionaryId,
            'pagination' => $pagination,
            'filter' => [
                'item' => $items,
            ],
        ]);
    }
}
