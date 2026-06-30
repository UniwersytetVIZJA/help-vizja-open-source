<?php

namespace App\Form\Application\Transformer;

use App\Database\Entity\Inventory;
use App\Database\Repository\InventoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Form\DataTransformerInterface;

final readonly class InventoryIdToEntityTransformer implements DataTransformerInterface
{
    /**
     * @param InventoryRepository $repo
     */
    public function __construct(private InventoryRepository $repo) {}

    /**
     * @param $value
     * @return string
     */
    public function transform($value): string
    {
        if (!$value instanceof Collection) {
            return '';
        }

        return implode(',', array_map(
            fn(Inventory $i) => (string)$i->getId(),
            $value->toArray()
        ));
    }

    /**
     * @param $value
     * @return Collection
     */
    public function reverseTransform($value): Collection
    {
        $out = new ArrayCollection();
        if ($value === null || $value === '') {
            return $out;
        }
        foreach (array_filter(array_map('trim', explode(',', (string)$value))) as $id) {
            if ($inv = $this->repo->find($id)) {
                $out->add($inv);
            }
        }

        return $out;
    }
}
