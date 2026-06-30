<?php

declare(strict_types=1);

namespace App\Core\Dictionary;

use App\Core\BaseManager;
use App\Database\Entity\Dictionary;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Class DictionaryManager
 * @package App\Core\Dictionary
 */
final class DictionaryManager extends BaseManager
{
    /**
     * DictionaryManager constructor
     * @param DictionaryRepository $dictionaryRepository
     * @param TranslatorInterface $translator
     */
    public function __construct(
        private readonly DictionaryRepository $dictionaryRepository,
        private readonly TranslatorInterface $translator,
    ) {}

    /**
     * @return array
     */
    public function findAll(): array
    {
        return $this->dictionaryRepository->findAll();
    }

    /**
     * @param string $dictionaryId
     * @return Dictionary
     */
    public function findOneById(string $dictionaryId): Dictionary
    {
        $dictionary = $this->dictionaryRepository->findOneById($dictionaryId);

        if (!$dictionary instanceof Dictionary) {
            throw new NotFoundHttpException($this->translator->trans('Nie znaleziono obiektu'));
        }

        return $dictionary;
    }
}
