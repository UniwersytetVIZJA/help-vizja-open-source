<?php

namespace App\Mailer\Mail\Application;

use App\Database\Entity\Application;
use App\Database\Entity\Student;
use App\Enum\User\NotificationLanguageEnum;
use App\Mailer\MailContentInterface;

readonly class StudentSubmitApplication implements MailContentInterface
{
    /**
     * @param string $userName
     * @param string $applicationId
     */
    public function __construct(
        private string $userName,
        private string $applicationId,
        private string $locale,
    ) {}

    /**
     * @param Student $student
     * @param Application $application
     * @return self
     */
    public static function fromEntity(Student $student, Application $application): self
    {
        return new self(
            $student->firstName . ' ' . $student->lastName,
            $application->applicationNumber,
            $student->notificationLanguage ?? NotificationLanguageEnum::Polski->value,
        );
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return match ($this->locale){
            NotificationLanguageEnum::Angielski->value => 'Confirmation of application submission',
            default => 'Potwierdzenie złożenia wniosku',
        };
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return match ($this->locale) {
            NotificationLanguageEnum::Angielski->value => 'mailer/application/eng/submit-application-eng.html',
            default => 'mailer/application/submit-application.html',
        };
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return [
            'userName' => $this->userName,
            'applicationId' => $this->applicationId,
        ];
    }
}

