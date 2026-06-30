<?php

declare(strict_types=1);

namespace App\Core\Student;

use App\Core\BaseManager;
use App\Database\Entity\Application;
use App\Database\Entity\Student;
use App\Verbis\API\PobierzOsobe;
use DateMalformedStringException;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumberUtil;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Class UserManager
 * @package App\Core\User
 */
final class StudentManager extends BaseManager
{
    /**
     * StudentManager constructor
     * @param StudentRepository $studentRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly StudentRepository $studentRepository, private readonly EntityManagerInterface $entityManager, private readonly UserPasswordHasherInterface $userPasswordHasher, private readonly PobierzOsobe $pobierzOsobe,
    ) {}

    /**
     * @return array
     */
    public function getAll(): array
    {
        return $this->studentRepository->getAll();
    }

    /**
     * @return QueryBuilder
     */
    public function getAllQueryBuilder(): QueryBuilder
    {
        return $this->studentRepository->getAllQueryBuilder();
    }

    /**
     * @param string $email
     * @return Student
     */
    public function getOneByEmail(string $email): Student
    {
        $user = $this->studentRepository->getOneByEmail($email);

        if (!$user instanceof Student) {
            throw new NotFoundHttpException('Nie znaleziono użytkownika');
        }

        return $user;
    }

    /**
     * @param string $userId
     * @return Student
     */
    public function getOneById(string $userId): Student
    {
        $student = $this->studentRepository->getOneById($userId);

        if (!$student instanceof Student) {
            throw new NotFoundHttpException('Nie znaleziono użytkownika');
        }

        return $student;
    }

    /**
     * @param Student $student
     * @param bool $flush
     * @return void
     */
    public function delete(Student $student, bool $flush = true): void
    {
        $this->entityManager->remove($student);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param Student $student
     * @return void
     */
    public function update(Student $student): void
    {
        $this->basePersister->update($student, true);
    }

    /**
     * @param Student $student
     * @param bool $flush
     * @return void
     */
    public function block(Student $student, bool $flush = true): void
    {
        $student->isActive = false;

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param Student $student
     * @param bool $flush
     * @return void
     */
    public function restore(Student $student, bool $flush = true): void
    {
        $student->isActive = true;

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param Student $student
     * @param Application $application
     * @return void
     */
    public function updateStudentInfo(Student $student, Application $application): void
    {
        if ($student->albumNumber === null) {
            $student->albumNumber = $application->albumNumber;
        }

        if ($student->phone === null) {
            $student->phone = $application->phone;
        }

        if ($student->faculty === null) {
            $student->faculty = $application->faculty;
        }

        if ($student->studyMode === null) {
            $student->studyMode = $application->studyMode;
        }

        if ($student->studyYear === null) {
            $student->studyYear = $application->year;
        }

        if ($student->studySemester === null) {
            $student->studySemester = $application->semester;
        }
    }

    public function createGuest(string $firstName, string $lastName, string $email, string $password): Student
    {
        $repo = $this->entityManager->getRepository(Student::class);
        $existing = $repo->findOneBy(['email' => $email]);

        if ($existing instanceof Student) {
            throw new RuntimeException('Nie można utworzyć konta.');
        }

        $guest = new Student();
        $guest->firstName = $firstName;
        $guest->lastName = $lastName;
        $guest->email = $email;
        $guest->password = $this->userPasswordHasher->hashPassword($guest, $password);
        $guest->isActive = true;
        $guest->roles = ['ROLE_GOSC'];

        $this->entityManager->persist($guest);
        $this->entityManager->flush();

        return $guest;
    }

    public function updateDataViaVerbis(Student $student): void
    {
        $osobaVerbis = $this->pobierzOsobe->pobierzOsobe($student);
        $albumVerbis = $this->pobierzOsobe->pobierzNrAlbumu($student);
        $wydzialVerbis = $this->pobierzOsobe->pobierzWydzial2($student)->nazwaPelna;
        $kierunekVerbis = $this->pobierzOsobe->pobierzKierunek($student)->nazwaSpecjalizacji;
        $trybZajecVerbis = $this->pobierzOsobe->pobierzTure($student)->trybProwadzeniaZajec;
        $semestrVerbis = $this->pobierzOsobe->pobierzLiczbeSemestrowTury($student);
        $rokVerbis = $this->pobierzOsobe->pobierzRokStudiow($student);

        $student->albumNumber = (int)$albumVerbis;
        $student->kierunekVerbis = (string)$kierunekVerbis;
        $student->trybZajecVerbis = (string)$trybZajecVerbis;
        $student->semestrVerbis = (int)$semestrVerbis;
        $student->rokStudiowVerbis = (int)$rokVerbis;
        $student->wydzialVerbis = (string)$wydzialVerbis;

        $student->phone = null;

        if (!empty($osobaVerbis->telefonKontaktowy)) {
            try {
                $phoneUtil = PhoneNumberUtil::getInstance();
                $student->phone = $phoneUtil->parse($osobaVerbis->telefonKontaktowy, 'PL');
            } catch (NumberParseException) {
                $student->phone = null;
            }
        }

        $this->entityManager->persist($student);
        $this->entityManager->flush();
    }

    public function updateWydzial(Student $student): void
    {
        $wydzialVerbis = $this->pobierzOsobe->pobierzWydzial2($student)->nazwaPelna;

        $student->wydzialVerbis = (string)$wydzialVerbis;

        $this->entityManager->persist($student);
        $this->entityManager->flush();
    }

    public function updateDataViaVerbisAdmin(Student $student): void
    {
        $albumVerbis = $this->pobierzOsobe->pobierzNrAlbumu($student);
        $wydzialVerbis = $this->pobierzOsobe->pobierzWydzial2($student)->nazwaSpecjalizacji;
        $kierunekVerbis = $this->pobierzOsobe->pobierzKierunek($student)->nazwaSpecjalizacji;
        $trybZajecVerbis = $this->pobierzOsobe->pobierzTure($student)->trybProwadzeniaZajec;
        $semestrVerbis = $this->pobierzOsobe->pobierzLiczbeSemestrowTury($student);
        $rokVerbis = $this->pobierzOsobe->pobierzRokStudiow($student);

        $student->albumNumber = (int)$albumVerbis;
        $student->kierunekVerbis = (string)$kierunekVerbis;
        $student->trybZajecVerbis = (string)$trybZajecVerbis;
        $student->semestrVerbis = (int)$semestrVerbis;
        $student->rokStudiowVerbis = (int)$rokVerbis;
        $student->wydzialVerbis = (string)$wydzialVerbis;

        $this->entityManager->persist($student);
        $this->entityManager->flush();
    }

    /**
     * @throws DateMalformedStringException
     */
    public function orzeczenieVerbisAdmin(Student $student): void
    {
        $verbis = $this->pobierzOsobe->pobierzOrzeczenie($student);

        $student->disabilityCountry = (string)$verbis->krajWydaniaOrzeczenia;
        $student->disabilityType = match (true) {
            is_array($verbis->przyczynyNiepelnosprawnosciPolskie)
            => $verbis->przyczynyNiepelnosprawnosciPolskie,

            empty($verbis->przyczynyNiepelnosprawnosciPolskie)
            => [],

            default
            => [(string)$verbis->przyczynyNiepelnosprawnosciPolskie],
        };
        $student->disabilityDegree = (string)$verbis->stopienNiepelnosprawnosci;

        if (!$verbis->wydaneNaStale) {
            $student->disabilityExpiration = !empty($verbis->terminWaznosci)
                ? new \DateTimeImmutable($verbis->terminWaznosci)
                : null;
        } else {
            $student->disabilityExpiration = null;
        }

        $this->entityManager->persist($student);
        $this->entityManager->flush();
    }

}
