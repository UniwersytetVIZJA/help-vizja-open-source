<?php

namespace App\Database\Repository;

use App\Database\Entity\Application\EducationalProcess;
use App\Database\Entity\Application\LanguageInterpreter;
use App\Database\Entity\Application\SpecialisedEquipment;
use App\Database\Repository\Application\EducationalProcessRepository;
use App\Database\Repository\Application\LanguageInterpreterRepository;
use App\Database\Repository\Application\SpecialisedEquipmentRepository;

class ApplicationFinder
{
    /**
     * @param EducationalProcessRepository $educationalProcessRepository
     * @param LanguageInterpreterRepository $languageInterpreterRepository
     * @param SpecialisedEquipmentRepository $specialisedEquipmentRepository
     */
    public function __construct(
        private readonly EducationalProcessRepository $educationalProcessRepository,
        private readonly LanguageInterpreterRepository $languageInterpreterRepository,
        private readonly SpecialisedEquipmentRepository $specialisedEquipmentRepository,
    ) {}

    /**
     * @param string $id
     * @return EducationalProcess|LanguageInterpreter|SpecialisedEquipment|null
     */
    public function findAnyById(string $id): EducationalProcess|LanguageInterpreter|SpecialisedEquipment|null
    {
        return $this->educationalProcessRepository->findOneById($id)
            ?? $this->languageInterpreterRepository->findOneById($id)
            ?? $this->specialisedEquipmentRepository->findOneById($id);
    }

    /**
     * @param string $studentId
     * @return object|null
     */
    public function findOneLatest(string $studentId): ?object
    {
        $pick = static function (?object $a, ?object $b): ?object {
            if (!$a) return $b;
            if (!$b) return $a;

            return ($a->createdAt >= $b->createdAt ? $a : $b);
        };

        $latest = null;
        $latest = $pick($latest, $this->specialisedEquipmentRepository->findLatest($studentId));
        $latest = $pick($latest, $this->languageInterpreterRepository->findLatest($studentId));
        $latest = $pick($latest, $this->educationalProcessRepository->findLatest($studentId));

        return $latest;
    }
}
