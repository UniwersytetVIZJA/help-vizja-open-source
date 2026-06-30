<?php

namespace App\Mailer\Mail\Application;

use App\Database\Entity\Application;
use App\Database\Entity\Student;
use App\Enum\User\NotificationLanguageEnum;
use App\Mailer\MailContentInterface;

readonly class EmployeeComment implements MailContentInterface
{

    /**
     * @param string $userName
     * @param string $applicationId
     * @param string $comment
     */
    public function __construct(
        private string $userName,
        private string $applicationId,
        private string $comment,
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
            $application->employeeComment,
            $student->notificationLanguage ?? NotificationLanguageEnum::Polski->value,
        );
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return match ($this->locale){
            NotificationLanguageEnum::Angielski->value => 'New comment in application no. ' . $this->applicationId,
            default => 'Nowy komentarz we wniosku nr ' . $this->applicationId,
        };
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return match ($this->locale) {
            NotificationLanguageEnum::Angielski->value => 'mailer/application/eng/employee-comment-eng.html',
            default => 'mailer/application/employee-comment.html',
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
            'comment' => $this->comment,
        ];
    }
}
