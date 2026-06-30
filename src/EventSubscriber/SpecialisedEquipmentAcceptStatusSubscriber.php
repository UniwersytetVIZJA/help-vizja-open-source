<?php

namespace App\EventSubscriber;

use App\Database\Entity\Application\SpecialisedEquipment;
use App\Database\Entity\Inventory;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;

#[AsDoctrineListener(event: Events::postUpdate)]
readonly class SpecialisedEquipmentAcceptStatusSubscriber implements EventSubscriber
{
    /**
     * @param EntityManagerInterface $em
     */
    public function __construct(private EntityManagerInterface $em) {}

    /**
     * @return array|string[]
     */
    public function getSubscribedEvents(): array
    {
        return [Events::preUpdate];
    }

    /**
     * Reaguje na aktualizację encji SpecialisedEquipment.
     *
     * Po zmianie statusu na „Zaakceptowany” metoda przypisuje studentowi
     * zarezerwowany sprzęt oraz ustawia okres wypożyczenia, zmieniając
     * status sprzętu na „Wypożyczony”.
     *
     * @param PostUpdateEventArgs $args Argumenty zdarzenia Doctrine
     *
     * @return void
     */
    public function __invoke(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if (!$entity instanceof SpecialisedEquipment) {
            return;
        }

        if ($entity->status !== 'Zaakceptowany') {
            return;
        }

        $em = $args->getObjectManager();

        $student = $entity->student;
        $rentStart = $entity->rentStart;
        $rentEnd = $entity->rentEnd;

        foreach ($entity->equipment as $inventory) {
            if ($inventory instanceof Inventory
                && $inventory->status === 'Zarezerwowany'
            ) {
                $inventory->status = 'Wypożyczony';
                $inventory->student = $student;
                $inventory->rentStart = $rentStart;
                $inventory->rentEnd = $rentEnd;
            }
        }

        $em->flush();
    }
}
