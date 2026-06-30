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
readonly class SpecialisedEquipmentDeclineStatusSubscriber implements EventSubscriber
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
     * Reaguje na odrzucenie encji SpecialisedEquipment.
     *
     * Po zmianie statusu na „Odrzucony” metoda przywraca status
     * powiązanego sprzętu na „Dostępny”, anulując wcześniejszą rezerwację.
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

        if ($entity->status !== 'Odrzucony') {
            return;
        }

        $em = $args->getObjectManager();

        foreach ($entity->equipment as $inventory) {
            if ($inventory instanceof Inventory && $inventory->status === 'Zarezerwowany') {
                $inventory->status = 'Dostępny';
            }
        }

        $em->flush();
    }
}
