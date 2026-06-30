<?php

namespace App\Mailer\Mail\Application;

use App\Database\Entity\Application;
use App\Database\Entity\Student;
use App\Enum\User\NotificationLanguageEnum;
use App\Mailer\MailContentInterface;

readonly class StatusChange implements MailContentInterface
{
    /**
     * @param string $userName
     * @param string $applicationId
     * @param string $applicationStatus
     */
    public function __construct(
        private string $userName,
        private string $applicationId,
        private string $applicationStatus,
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
            $application->status,
            $student->notificationLanguage ?? NotificationLanguageEnum::Polski->value,
        );
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return match ($this->locale){
            NotificationLanguageEnum::Angielski->value => 'A new status has been assigned to application no. ' . $this->applicationId,
            default => 'Nadano nowy status we wniosku nr ' . $this->applicationId,
        };
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return match ($this->locale) {
            NotificationLanguageEnum::Angielski->value => 'mailer/application/eng/status-change-eng.html',
            default => 'mailer/application/status-change.html',
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
            'status' => $this->applicationStatus,
        ];
    }
}

