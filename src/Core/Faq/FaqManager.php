<?php

namespace App\Core\Faq;

use App\Core\BaseManager;
use App\Database\Entity\Faq;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;

class FaqManager extends BaseManager
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * @throws ORMException
     */
    public function deleteFaq(Faq $faq): void
    {
        $this->basePersister->delete($faq, true);
    }

    public function create(Faq $faq): void
    {
        $this->entityManager->persist($faq);
        $this->entityManager->flush();
    }

    public function update(Faq $faq): void
    {
        $this->basePersister->update($faq, true);
    }
}
