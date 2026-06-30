<?php

declare(strict_types=1);

namespace App\Core\Application;

use App\Core\Application\Form\ApplicationForm;
use App\Core\BaseManager;
use App\Database\Entity\Application;
use App\Database\Entity\Application\LanguageInterpreter;
use App\Database\Entity\Dictionary\Item;
use App\Database\Entity\Student;
use App\Enum\Application\ApplicationDiscrEnum;
use App\Enum\Application\ApplicationPrefixEnum;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use function method_exists;

/**
 * Class ApplicationManager
 * @package App\Core\Application
 */
final class ApplicationManager extends BaseManager
{
    private const string SESSION_TYPE = 'application_type';
    private const string SESSION_ID = 'application_id';

    /**
     * ApplicationManager constructor
     * @param ApplicationForm $applicationForm
     * @param ApplicationRepository $applicationRepository
     * @param RequestStack $requestStack
     * @param Security $security
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        private readonly ApplicationForm $applicationForm,
        private readonly ApplicationRepository $applicationRepository,
        private readonly RequestStack $requestStack,
        private readonly Security $security,
        private readonly EntityManagerInterface $entityManager,

    ) {}

    /**
     * @return void
     */
    public function clearSession(): void
    {
        $session = $this->requestStack->getSession();
        $session->remove(self::SESSION_ID);
        $session->remove(self::SESSION_TYPE);
    }

    public function clearType(): void
    {
        $session = $this->requestStack->getSession();
        $session->remove(self::SESSION_TYPE);
    }

    /**
     * @param Application $application
     * @return FormInterface
     */
    public function createFormByType(Application $application, array $options = []): FormInterface
    {
        return $this->applicationForm->create($application, $options);
    }

    /**
     * @param Application $application
     * @return void
     * @throws ORMException
     */
    public function create(Application $application): void
    {
        if (!$application->type instanceof Item) {
            throw new \InvalidArgumentException('Brak wybranego typu wniosku.');
        }

        $code = method_exists($application->type, 'getHiddenValue')
            ? (string)$application->type->getHiddenValue()
            : (string)($application->type->hiddenValue ?? '');

        if ($code === '') {
            throw new \InvalidArgumentException('Nieprawidłowy kod typu wniosku.');
        }

        $child = $this->newForType($code);

        $child->type = $application->type;

        $studentId = $this->security->getUser()->getUserIdentifier();
        $child->student = $this->entityManager->getReference(Student::class, $studentId);
        $this->entityManager->persist($child);
        $this->entityManager->flush();

        $this->basePersister->create($child, true);
        $session = $this->requestStack->getSession();
        $session->set(self::SESSION_ID, $child->id);
        $session->set(self::SESSION_TYPE, $code);
    }

    private function newForType(string $code): Application
    {
        return match ($code) {
            'language-interpreter' => new LanguageInterpreter(),
            'educational-process' => new Application\EducationalProcess(),
            'specialised-equipment' => new Application\SpecialisedEquipment(),
            'teaching-assistant' => new Application\TeachingAssistant(),

            default => throw new \InvalidArgumentException("Nieobsługiwany typ: $code"),
        };
    }

    /**
     * @return Application
     */
    public function getApplicationFromSession(): Application
    {
        $session = $this->requestStack->getSession();
        $id = $session->get(self::SESSION_ID);

        if (!$id) {
            throw new \LogicException('Brak wniosku w sesji, utwórz nowy wniosek');
        }

        $application = $this->applicationRepository->findOneById($id);
        if (!$application instanceof Application) {
            throw new \LogicException('Wniosek z zapisanym identyfikatorem nie istnieje.');
        }

        return $application;
    }

    public function removeById(string $id): void
    {
        $application = $this->entityManager->getRepository(Application::class)->find($id);

        if ($application) {
            $this->entityManager->remove($application);
            $this->entityManager->flush();
        }
    }

    /**
     * @param Application $application
     */
    public function assignApplicationNumber(Application $application): void
    {
        $maxTries = 5;
        $tries = 0;

        do {
            $tries++;

            $appType = $application->type->hiddenValue;
            $prefix = match ($appType) {
                ApplicationDiscrEnum::EDUCATIONAL_PROCESS->value => ApplicationPrefixEnum::EDUCATIONAL_PROCESS->value,
                ApplicationDiscrEnum::TEACHING_ASSISTANT->value => ApplicationPrefixEnum::TEACHING_ASSISTANT->value,
                ApplicationDiscrEnum::SPECIALISED_EQUIPMENT->value => ApplicationPrefixEnum::SPECIALISED_EQUIPMENT->value,
                ApplicationDiscrEnum::LANGUAGE_INTERPRETER->value => ApplicationPrefixEnum::LANGUAGE_INTERPRETER->value,
                default => throw new \LogicException(sprintf('Brak prefixu dla typu wniosku "%s"', $appType)),
            };
            $number = $this->applicationRepository->generateApplicationNumber($prefix);
            $application->applicationNumber = $number;

            try {
                $this->update($application);
                $success = true;
            } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
                $success = false;
                $this->entityManager->clear();
                $application = $this->applicationRepository->find($application->id);
            }
        } while (!$success && $tries < $maxTries);

        if (!$success) {
            throw new \RuntimeException('Nie udało się wygenerować numeru wniosku.');
        }
    }

    /**
     * @param Application $application
     * @return void
     */
    public function update(Application $application): void
    {
        $this->basePersister->update($application, true);
    }

    public function getApplicationFilesCounts(Application $application): array
    {
        $counts = [
            'files' => 0,
            'decision' => 0,
            'schedule' => 0,
            'statement' => 0,
        ];

        foreach ($application->files as $file) {
            $fieldName = $file->category2 ?? 'files';
            if (isset($counts[$fieldName])) {
                $counts[$fieldName]++;
            }
        }

        return $counts;
    }
}
