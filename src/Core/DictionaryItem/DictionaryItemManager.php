<?php

declare(strict_types=1);

namespace App\Core\DictionaryItem;

use App\Core\BaseManager;
use App\Database\Entity\Dictionary;
use App\Database\Entity\Dictionary\Item;
use App\Enum\Dictionary\DictionaryNameEnum;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DictionaryItemManager
 * @package App\Core\DictionaryItem
 */
final class DictionaryItemManager extends BaseManager
{
    /**
     * DictionaryItemManager constructor
     * @param DictionaryItemRepository $dictionaryItemRepository
     * @param TranslatorInterface $translator
     */
    public function __construct(
        private readonly DictionaryItemRepository $dictionaryItemRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * @param Item $item
     * @param Dictionary $dictionary
     * @return void
     */
    public function create(Item $item, Dictionary $dictionary): void
    {
        $item->dictionary = $dictionary;
        $this->basePersister->create($item, true);
    }

    /**
     * @param string $dictionaryId
     * @return array
     */
    public function findAllByDictionaryId(string $dictionaryId): array
    {
        return $this->dictionaryItemRepository->findAllByDictionaryId($dictionaryId);
    }

    /**
     * @param DictionaryNameEnum $dictionaryName
     * @return array
     */
    public function findAllByDictionaryName(DictionaryNameEnum $dictionaryName): array
    {
        return $this->dictionaryItemRepository->findAllByDictionaryName($dictionaryName);
    }

    /**
     * @param string $dictionaryItemId
     * @return Item
     */
    public function findOneById(string $dictionaryItemId): Item
    {
        $item = $this->dictionaryItemRepository->findOneById($dictionaryItemId);

        if (!$item instanceof Item) {
            throw new NotFoundHttpException($this->translator->trans('Nie znaleziono obiektu'));
        }

        return $item;
    }

    /**
     * @param Item $item
     * @return void
     */
    public function update(Item $item): void
    {
        $this->basePersister->update($item, true);
    }
    
}
