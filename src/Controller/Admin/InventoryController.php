<?php

namespace App\Controller\Admin;

use App\Core\Inventory\InventoryManager;
use App\Database\Entity\Dictionary\Item;
use App\Database\Entity\Inventory;
use App\Database\Entity\InventoryType;
use App\Database\Repository\AnnouncementsRepository;
use App\Database\Repository\InventoryRepository;
use App\Database\Repository\InventoryTypeRepository;
use App\Database\Repository\ItemRepository;
use App\Enum\Dictionary\DictionaryNameEnum;
use App\Form\Inventory\CreateInventoryForm;
use App\Form\Inventory\CreateInventorySubtypeForm;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;
use function date;

class InventoryController extends AbstractController
{
    /**
     * @param AnnouncementsRepository $announcementsRepository
     * @param EntityManagerInterface $entityManager
     * @param InventoryRepository $inventoryRepository
     * @param ItemRepository $dictionaryItemManager
     * @param InventoryManager $inventoryManager
     * @param InventoryTypeRepository $inventoryTypeRepository
     * @param PaginatorInterface $paginator
     */
    public function __construct(
        private readonly AnnouncementsRepository $announcementsRepository,
        private readonly EntityManagerInterface $entityManager, private readonly InventoryRepository $inventoryRepository,
        private readonly ItemRepository $dictionaryItemManager, private readonly InventoryManager $inventoryManager, private readonly InventoryTypeRepository $inventoryTypeRepository, private readonly PaginatorInterface $paginator, private readonly TranslatorInterface $translator
    ) {}

    /**
     * Wyświetla listę pozycji inwentaryzacji w panelu administracyjnym.
     *
     * Prezentuje sprzęt zdefiniowany w słowniku oraz wyniki
     * z paginacją.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Request $request Żądanie HTTP zawierające numer strony
     *
     * @return Response Widok listy inwentaryzacji z paginacją
     */
    #[Route('/admin/inwentaryzacja', name: 'admin_inventory')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function index(Request $request): Response
    {
        $inventory = $this->inventoryRepository->findAllByDictionaryNameQueryBuilder(DictionaryNameEnum::SPRZET);

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($inventory, $page);

        return $this->render('admin/inventory/index.html.twig', [
            'inventories' => $inventory,
            'pagination' => $pagination,
        ]);
    }

    /**
     * Wyświetla inwentaryzację dla wybranego typu sprzętu.
     *
     * Metoda pobiera pozycje inwentaryzacji powiązane z danym typem
     * oraz prezentuje je z paginacją i liczbą rekordów.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Item $item Typ sprzętu (element słownika)
     * @param Request $request Żądanie HTTP zawierające numer strony
     *
     * @return Response Widok listy inwentaryzacji według typu sprzętu
     */
    #[Route('/admin/inwentaryzacja/sprzet/{item}', name: 'admin_inventory_equipment')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function equipmentType(Item $item, Request $request): Response
    {
        $search = trim((string)$request->query->get('search', ''));

        $eq = $this->inventoryTypeRepository->findByDictionaryItemType($item, $search);

        $types = $eq->getQuery()->getResult();

        $typeIds = array_map(
            static fn($type) => $type->id,
            $types
        );

        $count = $this->inventoryRepository->countByTypeIds($typeIds);

        $page = $request->query->getInt('page', 1);
        $pagination = $this->paginator->paginate($eq, $page);

        return $this->render('admin/inventory/inv-type.html.twig', [
            'item' => $item,
            'inventories' => $pagination,
            'inventoryCount' => $count,
            'pagination' => $pagination,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    /**
     * Wyświetla formularz dodawania nowego typu inwentaryzacji dla wybranego sprzętu
     * oraz obsługuje jego zapis.
     *
     * Metoda tworzy nowy podtyp sprzętu powiązany z elementem słownika,
     * waliduje dane formularza i zapisuje je w systemie. Po powodzeniu
     * przekierowuje do listy inwentaryzacji danego typu.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Item $dictionaryItemId Element słownika reprezentujący typ sprzętu
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza lub przekierowanie po zapisie
     */
    #[Route('/admin/inwentaryzacja/sprzet/{dictionaryItemId}/dodaj-typ', name: 'admin_create_inv_type')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function create(Item $dictionaryItemId, Request $request): Response
    {
        $inv = new InventoryType();
        $inv->inv = $dictionaryItemId;

        $form = $this->createForm(CreateInventorySubtypeForm::class, $inv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inv = $form->getData();

            $this->inventoryManager->createType($inv);
            $this->addFlash('success', $this->translator->trans('Dodano element'));

            return $this->redirectToRoute('admin_inventory_equipment', [
                'item' => $inv->inv->id,
            ]);
        }

        return $this->render('admin/inventory/create-inv-type.html.twig', [
            'form' => $form->createView(),
            'inv' => $inv,
            'item' => $dictionaryItemId,
        ]);
    }

    /**
     * Wyświetla formularz edycji typu inwentaryzacji oraz obsługuje zapis zmian.
     *
     * Metoda umożliwia modyfikację istniejącego podtypu sprzętu,
     * waliduje dane formularza i zapisuje zmiany w systemie.
     * Po powodzeniu przekierowuje do listy inwentaryzacji
     * powiązanej z danym typem sprzętu.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Item $item Typ sprzętu (element słownika)
     * @param InventoryType $inventoryTypeId Edytowany typ inwentaryzacji
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza edycji lub przekierowanie po zapisie
     */
    #[Route('/admin/inwentaryzacja/sprzet/{item}/edytuj-typ/{inventoryTypeId}', name: 'admin_update_inv_type')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateType(Item $item, InventoryType $inventoryTypeId, Request $request): Response
    {
        $form = $this->createForm(CreateInventorySubtypeForm::class, $inventoryTypeId);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inv = $form->getData();

            $this->inventoryManager->updateType($inv);
            $this->addFlash('success', $this->translator->trans('Edytowano element'));

            return $this->redirectToRoute('admin_inventory_equipment', [
                'item' => $inv->inv->id,
            ]);
        }

        return $this->render('admin/inventory/create-inv-type.html.twig', [
            'form' => $form->createView(),
            'item' => $item,
            'type' => $inventoryTypeId,
        ]);
    }

    /**
     * Usuwa typ inwentaryzacji powiązany z wybranym sprzętem.
     *
     * Metoda usuwa istniejący typ inwentaryzacji z systemu
     * i po wykonaniu operacji przekierowuje do listy inwentaryzacji
     * danego typu sprzętu.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param string $inventoryTypeId Identyfikator typu inwentaryzacji
     *
     * @return Response Przekierowanie po usunięciu
     */
    #[Route('/admin/inwentaryzacja/sprzet/usun-typ/{inventoryTypeId}', name: 'admin_delete_inv_type')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function deleteType(string $inventoryTypeId): Response
    {
        $inventoryType = $this->inventoryTypeRepository->find($inventoryTypeId);

        $this->inventoryManager->deleteType($inventoryType);

        if (!$inventoryType) {
            throw $this->createNotFoundException('Typ nie istnieje');
        }

        return $this->redirectToRoute('admin_inventory_equipment', [
            'item' => $inventoryType->inv->id,
        ]);
    }

    /**
     * Wyświetla listę pozycji inwentaryzacji dla wybranego typu sprzętu.
     *
     * Metoda pobiera pozycje inwentaryzacji powiązane z danym typem sprzętu
     * i prezentuje je w formie listy z paginacją.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Item $item Typ sprzętu (element słownika)
     * @param InventoryType $inventoryTypeId Typ inwentaryzacji
     * @param Request $request Żądanie HTTP zawierające numer strony
     *
     * @return Response Widok listy pozycji inwentaryzacji z paginacją
     */
    #[Route('/admin/inwentaryzacja/sprzet/{item}/lista/{inventoryTypeId}', name: 'admin_inventory_items')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function equipment(Item $item, InventoryType $inventoryTypeId, Request $request): Response
    {
        $eq = $this->inventoryRepository->findByDictionaryItem($inventoryTypeId);

        return $this->render('admin/inventory/inv-type-items.html.twig', [
            'item' => $item,
            'inventories' => $eq,
            'type' => $inventoryTypeId,
            'pagination' => $eq,
        ]);
    }

    /**
     * Wyświetla formularz dodawania nowej pozycji inwentaryzacji
     * dla wybranego typu sprzętu oraz obsługuje jej zapis.
     *
     * Metoda tworzy nową pozycję inwentaryzacji powiązaną z danym
     * typem sprzętu, waliduje dane formularza i zapisuje je w systemie.
     * Po powodzeniu przekierowuje do listy pozycji inwentaryzacji.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Item $item Typ sprzętu (element słownika)
     * @param InventoryType $inventoryTypeId Typ inwentaryzacji
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza lub przekierowanie po zapisie
     */
    #[Route('/admin/inwentaryzacja/sprzet/{item}/{inventoryTypeId}/dodaj', name: 'admin_create_inv_type_item')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function createInv(Item $item, InventoryType $inventoryTypeId, Request $request): Response
    {
        $inventoryType = $this->inventoryTypeRepository->find($inventoryTypeId);

        $inv = new Inventory();
        $inv->equipment = $inventoryType;

        $form = $this->createForm(CreateInventoryForm::class, $inv);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inv = $form->getData();

            $this->inventoryManager->createInv($inv);
            $this->addFlash('success', $this->translator->trans('Dodano element'));

            return $this->redirectToRoute('admin_inventory_items', [
                'item' => $item->id,
                'inventoryTypeId' => $inventoryTypeId->id,
            ]);
        }

        return $this->render('admin/inventory/create-inv.html.twig', [
            'form' => $form->createView(),
            'inv' => $inv,
            'item' => $item,
            'type' => $inventoryType,
        ]);
    }

    /**
     * Eksportuje inwentaryzację wybranego typu sprzętu do pliku Excel (XLSX).
     *
     * Metoda pobiera pozycje inwentaryzacji powiązane z danym typem sprzętu,
     * generuje arkusz XLSX i zwraca go jako odpowiedź strumieniowaną do pobrania.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param string $inventoryTypeId Identyfikator typu sprzętu
     *
     * @return StreamedResponse Plik XLSX zwracany jako strumień do pobrania
     */
    #[Route('/admin/inwentaryzacja/sprzet/export-excel/{inventoryTypeId}', name: 'admin_inv_excel_export')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function exportExcel(string $inventoryTypeId): StreamedResponse
    {
        $type = $this->inventoryTypeRepository->find($inventoryTypeId);
        if (!$type) {
            throw $this->createNotFoundException('Nie znaleziono typu sprzętu');
        }

        $inventories = $this->inventoryRepository->findBy(['equipment' => $type]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray([
            ['Numer seryjny', 'Opis', 'Status', 'Wypożyczone przez', 'Termin wypożyczenia'],
        ], null, 'A1');

        $rowNum = 2;

        /** @var Inventory $inv */
        foreach ($inventories as $inv) {
            if ($inv->student) {
                $student = $inv->student->firstName . ' ' . $inv->student->lastName;
            } else {
                $student = ' ';
            }

            if ($inv->rentStart && $inv->rentEnd) {
                $rentTerm = $inv->rentStart->format('d.m.Y') . ' - ' . $inv->rentEnd->format('d.m.Y');
            } else {
                $rentTerm = ' ';
            }

            if ($inv->description) {
                $description = $inv->description;
            } else {
                $description = ' ';
            }

            $sheet->fromArray([[
                $inv->serialNumber,
                $description,
                $inv->status,
                $student,
                $rentTerm,
            ]], null, "A{$rowNum}");

            $rowNum++;
        }

        $sheet->getStyle('A1:E1')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
        $sheet->getColumnDimension('C')->setAutoSize(true);
        $sheet->getColumnDimension('D')->setAutoSize(true);
        $sheet->getColumnDimension('E')->setAutoSize(true);

        $writer = new Xlsx($spreadsheet);

        $fileName = sprintf(
            'inwentaryzacja-%s-%s.xlsx',
            $type->type ?? 'sprzet',
            date('Y-m-d')
        );

        $response = new StreamedResponse(function () use ($writer) {
            $writer->save('php://output');
        });

        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', 'attachment;filename="' . $fileName . '"');
        $response->headers->set('Cache-Control', 'max-age=0');

        return $response;
    }

    /**
     * Wyświetla formularz edycji pozycji inwentaryzacji oraz obsługuje zapis zmian.
     *
     * Metoda umożliwia edycję wybranej pozycji inwentaryzacji dla danego typu sprzętu.
     * Jeśli status zostanie ustawiony na „Dostępny”, czyszczone są dane wypożyczenia
     * (student oraz terminy wypożyczenia).
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Item $item Typ sprzętu (element słownika)
     * @param InventoryType $inventoryId Typ inwentaryzacji
     * @param Inventory $inventory Edytowana pozycja inwentaryzacji
     * @param Request $request Żądanie HTTP zawierające dane formularza
     *
     * @return Response Widok formularza edycji lub przekierowanie po zapisie
     */
    #[Route('/admin/inwentaryzacja/sprzet/{item}/{inventoryId}/{inventory}/edytuj', name: 'admin_update_inv_item')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function updateInv(Item $item, InventoryType $inventoryId, Inventory $inventory, Request $request): Response
    {
        $form = $this->createForm(CreateInventoryForm::class, $inventory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inv = $form->getData();

            if ($inv->status === 'Dostępny') {
                $inv->student = null;
                $inv->rentStart = null;
                $inv->rentEnd = null;
            }

            $this->inventoryManager->updateInv($inv);
            $this->addFlash('success', $this->translator->trans('Edytowano element'));

            return $this->redirectToRoute('admin_inventory_items', [
                'item' => $item->id,
                'inventoryTypeId' => $inventoryId->id,
            ]);
        }

        return $this->render('admin/inventory/update-inv.html.twig', [
            'form' => $form->createView(),
            'inv' => $inventory,
            'item' => $item,
            'type' => $inventoryId,
        ]);
    }

    /**
     * Rejestruje zwrot pozycji inwentaryzacji.
     *
     * Metoda oznacza wybraną pozycję inwentaryzacji jako zwróconą
     * (zakończenie wypożyczenia) i przekierowuje do listy inwentaryzacji.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param string $inventoryId Identyfikator pozycji inwentaryzacji
     * @param Request $request Żądanie HTTP
     *
     * @return Response Przekierowanie po zarejestrowaniu zwrotu
     */
    #[Route('/admin/inwentaryzacja/sprzet/{inventoryId}/dodaj-zwrot', name: 'admin_return_inv_item')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function returnInv(string $inventoryId, Request $request): Response
    {
        $inventory = $this->inventoryRepository->find($inventoryId);

        $this->inventoryManager->returnInv($inventory);

        $this->addFlash('success', $this->translator->trans('Edytowano element'));

        return $this->redirectToRoute('admin_inventory');
    }

    /**
     * Usuwa wybraną pozycję inwentaryzacji.
     *
     * Metoda trwale usuwa pozycję inwentaryzacji powiązaną
     * z danym typem sprzętu i po wykonaniu operacji
     * przekierowuje do listy pozycji inwentaryzacji.
     *
     * Dostęp ograniczony do użytkowników z rolą ROLE_EMPLOYEE.
     *
     * @param Item $item Typ sprzętu (element słownika)
     * @param InventoryType $inventoryType Typ inwentaryzacji
     * @param Inventory $inventory Usuwana pozycja inwentaryzacji
     *
     * @return Response Przekierowanie po usunięciu
     */
    #[Route('/admin/inwentaryzacja/sprzet/{item}/typ/{inventoryType}/{inventory}/usun-sprzet', name: 'admin_delete_inv')]
    #[IsGranted('ROLE_EMPLOYEE')]
    public function deleteInv(Item $item, InventoryType $inventoryType, Inventory $inventory): Response
    {
        $this->inventoryManager->deleteInventory($inventory);

        return $this->redirectToRoute('admin_inventory_items', [
            'item' => $item->id,
            'inventoryTypeId' => $inventoryType->id,
        ]);
    }

}

