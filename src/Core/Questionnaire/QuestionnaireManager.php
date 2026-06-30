<?php

namespace App\Core\Questionnaire;

use App\Core\BaseManager;
use App\Database\Entity\Questionnaire;
use Doctrine\ORM\EntityManagerInterface;

class QuestionnaireManager extends BaseManager
{
    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(private readonly EntityManagerInterface $entityManager) {}

    /**
     * @param Questionnaire $questionnaire
     * @param bool $flush
     * @return void
     */
    public function createQuestionnaire(Questionnaire $questionnaire, bool $flush = true): void
    {
        $this->basePersister->create($questionnaire, $flush);
    }
}
