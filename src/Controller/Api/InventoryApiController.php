<?php

namespace App\Controller\Api;

use App\Database\Entity\Dictionary\Item;
use App\Database\Entity\Inventory;
use App\Database\Entity\InventoryType;
use App\Database\Repository\InventoryRepository;
use App\Database\Repository\InventoryTypeRepository;
use App\Database\Repository\ItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/inventory', name: 'api_inventory_')]
class InventoryApiController extends AbstractController
{
    public function __construct(
        private readonly ItemRepository $items,
        private readonly InventoryTypeRepository $types,
        private readonly InventoryRepository $inventories,
        private readonly EntityManagerInterface $em,
        private readonly RequestStack $requestStack,
    ) {}

    private function getReservedFromSession(): array
    {
        return $this->requestStack->getSession()->get('reserved_inventory', []);
    }

    private function setReservedInSession(array $reserved): void
    {
        $this->requestStack->getSession()->set('reserved_inventory', $reserved);
    }

    #[Route('/items', name: 'items', methods: ['GET'])]
    public function items(): JsonResponse
    {
        $list = $this->items->findActiveEquipmentItems();

        return $this->json(array_map(fn(Item $i) => [
            'id' => (string) $i->getId(),
            'value' => $i->value ?? (string) $i,
        ], $list));
    }

    #[Route('/types', name: 'types_for_item', methods: ['GET'])]
    public function typesForItem(Request $request): JsonResponse
    {
        $itemId = (string) $request->query->get('itemId');

        /** @var Item|null $item */
        $item = $this->items->find($itemId);

        if (!$item) {
            return $this->json([], 200);
        }

        $types = $this->types->findByDictionaryItemType($item);

        $counts = $this->inventories->countAvailableByTypeIds(
            array_map(fn($t) => $t->getId(), $types)
        );

        return $this->json(array_map(function (InventoryType $t) use ($counts) {
            return [
                'id' => (string) $t->getId(),
                'type' => $t->type,
                'available' => $counts[$t->getId()] ?? 0,
            ];
        }, $types));
    }

    #[Route('/reserved-session', name: 'reserved_session', methods: ['GET'])]
    public function reservedSession(): JsonResponse
    {
        return $this->json($this->getReservedFromSession());
    }

    #[Route('/reserve', name: 'reserve_first_free', methods: ['POST'])]
    public function reserveFirstFree(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?: [];

        $itemId = $payload['itemId'] ?? null;
        $typeId = $payload['typeId'] ?? null;

        if (!$itemId) {
            return $this->json(['error' => 'ID elementu sprzętu jest wymagane'], 400);
        }

        if (!$typeId) {
            return $this->json(['error' => 'ID typu sprzętu jest wymagane'], 400);
        }

        /** @var InventoryType|null $type */
        $type = $this->types->find($typeId);

        if (!$type) {
            return $this->json(['error' => 'Nie znaleziono typu sprzętu'], 404);
        }

        $sessionKey = $itemId . '::' . $type->getId();
        $reserved = $this->getReservedFromSession();

        if (isset($reserved[$sessionKey])) {
            return $this->json([
                'inventoryId' => (string) $reserved[$sessionKey],
                'alreadyReserved' => true,
            ]);
        }

        $this->em->beginTransaction();

        try {
            /** @var Inventory|null $inv */
            $inv = $this->inventories->findOneFreeByTypeForUpdate($type);

            if (!$inv) {
                $this->em->rollback();

                return $this->json(['error' => 'Brak wolnego sprzętu'], 409);
            }

            $inv->status = 'Zarezerwowany';

            $this->em->flush();

            $left = $this->inventories->countAvailableByTypeIds([
                $type->getId(),
            ])[$type->getId()] ?? 0;

            $this->em->commit();

            $reserved[$sessionKey] = (string) $inv->getId();
            $this->setReservedInSession($reserved);

            return $this->json([
                'inventoryId' => (string) $inv->getId(),
                'availableLeft' => $left,
            ]);
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }

    #[Route('/cancel', name: 'cancel_reservation', methods: ['POST'])]
    public function cancelReservation(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?: [];
        $invId = $payload['inventoryId'] ?? null;

        if (!$invId) {
            return $this->json(['ok' => true]);
        }

        /** @var Inventory|null $inv */
        $inv = $this->inventories->find($invId);

        if ($inv && $inv->status === 'Zarezerwowany') {
            $inv->status = 'Dostępny';
            $this->em->flush();
        }

        $reserved = $this->getReservedFromSession();

        foreach ($reserved as $key => $reservedInventoryId) {
            if ((string) $reservedInventoryId === (string) $invId) {
                unset($reserved[$key]);
            }
        }

        $this->setReservedInSession($reserved);

        return $this->json(['ok' => true]);
    }

    #[Route('/clear-session', name: 'clear_session', methods: ['POST'])]
    public function clearSession(): JsonResponse
    {
        $this->setReservedInSession([]);

        return $this->json(['ok' => true]);
    }
}
