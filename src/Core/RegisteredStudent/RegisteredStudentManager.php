<?php

namespace App\Core\RegisteredStudent;

use App\Core\BaseManager;
use App\Database\Entity\RegisteredStudent;
use App\Database\Entity\Registration;
use App\Database\Entity\Student;
use App\Database\Repository\RegisteredStudentRepository;
use App\Database\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Request;
use function method_exists;

class RegisteredStudentManager extends BaseManager
{
    /**
     * @param RegisteredStudentRepository $registrationRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly RegisteredStudentRepository $registrationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param RegisteredStudent $registration
     * @return void
     */
    public function create(RegisteredStudent $registration): void
    {
        $this->basePersister->create($registration, true);

    }

    public function update(RegisteredStudent $registeredStudent): void
    {
        $this->basePersister->update($registeredStudent, true);
    }
}
