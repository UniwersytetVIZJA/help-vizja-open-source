<?php

namespace App\Core\Registration;

use App\Core\BaseManager;
use App\Database\Entity\RegisteredStudent;
use App\Database\Entity\Registration;
use App\Database\Entity\Student;
use App\Database\Repository\RegistrationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Component\HttpFoundation\Request;
use function method_exists;

class RegistrationManager extends BaseManager
{
    /**
     * @param RegistrationRepository $registrationRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly RegistrationRepository $registrationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    /**
     * @param Registration $registration
     * @return void
     */
    public function create(Registration $registration): void
    {
        $this->entityManager->persist($registration);
        $this->entityManager->flush();
    }

    /**
     * @param Registration $registration
     * @param string|null $joinUrl
     * @param string|null $eventId
     * @param bool $flush
     * @return void
     */
    public function updateTeams(Registration $registration, ?string $joinUrl, ?string $eventId, bool $flush = true): void
    {
        if (method_exists($registration, 'setTeamsJoinUrl')) {
            $registration->setTeamsJoinUrl($joinUrl);
        } else if (property_exists($registration, 'teamsMeetingUrl')) {
            $registration->teamsMeetingUrl = $joinUrl;
            $registration->eventId = $eventId;
        } else {
            throw new \LogicException('Registration nie posiada setTeamsJoinUrl() ani pola teamsMeetingUrl.');
        }
        $this->entityManager->persist($registration);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param Student $student
     * @param Registration $registration
     * @param RegisteredStudent $rs
     * @param bool $flush
     * @return void
     */
    public function createStudentRegistration(Student $student, Registration $registration, RegisteredStudent $rs, bool $flush = true): void
    {
        $this->entityManager->wrapInTransaction(function () use ($rs, $student, $registration, $flush) {
            $rs->student = $student;
            $rs->registration = $registration;

            $this->entityManager->persist($rs);

            if ($flush) {
                $this->entityManager->flush();
            }
        });
    }

    /**
     * @param Student $student
     * @param Registration $registration
     * @param bool $flush
     * @return void
     */
    public function cancelStudentRegistration(Student $student, Registration $registration, bool $flush = true): void
    {
        $this->entityManager->wrapInTransaction(function () use ($student, $registration, $flush) {
            $rs = $this->entityManager->getRepository(RegisteredStudent::class)->findOneBy([
                'registration' => $registration,
                'student' => $student,
            ]);
            if (!$rs) {
                return;
            }
            $this->entityManager->remove($rs);
            if ($flush) {
                $this->entityManager->flush();
            }
        });
    }

    /**
     * @throws ORMException
     */
    public function deleteRegistration(Registration $registration, bool $flush = true): void
    {
        $entity = $this->entityManager->contains($registration)
            ? $registration
            : $this->entityManager->getReference(Registration::class, $registration->getId());

        $this->entityManager->remove($entity);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param Registration $registration
     * @param Request $request
     * @return Registration
     */
    public function updateRegistration(Registration $registration, Request $request): Registration
    {
        $registration->teamsMeetingUrl = $request->request->get('teamsMeetingUrl', $registration->teamsMeetingUrl);

        $this->entityManager->persist($registration);
        $this->entityManager->flush();

        return $registration;
    }

    /**
     * @param Student $student
     * @param RegisteredStudent $registeredStudent
     * @return void
     */
    public function updateStudentInfo(Student $student, RegisteredStudent $registeredStudent): void
    {
        if ($student->albumNumber === null) {
            $student->albumNumber = $registeredStudent->albumNumber;
        }
        if ($student->phone === null) {
            $student->phone = $registeredStudent->phone;
        }
    }
}
