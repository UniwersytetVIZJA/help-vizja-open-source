<?php

namespace App\Core\Inventory;

use App\Core\BaseManager;
use App\Database\Entity\Inventory;
use App\Database\Entity\InventoryType;
use Doctrine\ORM\EntityManagerInterface;

class InventoryManager extends BaseManager
{

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    /**
     * @param Inventory $inv
     * @param bool $flush
     * @return void
     */
    public function createInv(Inventory $inv, bool $flush = true): void
    {
        $this->basePersister->create($inv, $flush);
    }

    /**
     * @param Inventory $inv
     * @param bool $flush
     * @return void
     */
    public function updateInv(Inventory $inv, bool $flush = true): void
    {
        $this->basePersister->update($inv, $flush);
    }

    /**
     * @param InventoryType $inv
     * @param bool $flush
     * @return void
     */
    public function createType(InventoryType $inv, bool $flush = true): void
    {
        $this->basePersister->create($inv, $flush);
    }

    /**
     * @param InventoryType $inv
     * @param bool $flush
     * @return void
     */
    public function updateType(InventoryType $inv, bool $flush = true): void
    {
        $this->basePersister->update($inv, $flush);
    }

    /**
     * @param InventoryType $inv
     * @param bool $flush
     * @return void
     */
    public function deleteType(InventoryType $inv, bool $flush = true): void
    {
        $this->basePersister->delete($inv, $flush);
    }

    /**
     * @param Inventory $inv
     * @param bool $flush
     * @return void
     */
    public function deleteInventory(Inventory $inv, bool $flush = true): void
    {
        $this->basePersister->delete($inv, $flush);
    }

    /**
     * @param Inventory $inv
     * @param bool $flush
     * @return void
     */
    public function returnInv(Inventory $inv, bool $flush = true): void
    {
        $inv->status = 'Dostępny';
        $inv->rentStart = null;
        $inv->rentEnd = null;
        $inv->student = null;

        $this->entityManager->flush();
    }
}
