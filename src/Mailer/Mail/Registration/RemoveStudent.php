<?php

namespace App\Mailer\Mail\Registration;

use App\Database\Entity\Registration;
use App\Database\Entity\Student;
use App\Enum\User\NotificationLanguageEnum;
use App\Mailer\MailContentInterface;

final readonly class RemoveStudent implements MailContentInterface
{
    /**
     * @param string $userName
     * @param string $title
     * @param string $specialist
     */
    public function __construct(
        private string $userName,
        private string $title,
        private string $specialist,
        private string $locale,
    ) {}

    /**
     * @param Student $student
     * @param Registration $registration
     * @return self
     */
    public static function fromEntity(Student $student, Registration $registration): self
    {
        return new self(
            $student->firstName . ' ' . $student->lastName,
            $registration->title->value,
            $registration->specialist->firstName . ' ' . $registration->specialist->lastName,
            $registration->teamsMeetingUrl,
            $student->notificationLanguage ?? NotificationLanguageEnum::Polski->value,
        );
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return match ($this->locale) {
            NotificationLanguageEnum::Angielski->value => 'Removal from the register',
            default => 'Usunięcie z zapisu',
        };
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return match ($this->locale) {
            NotificationLanguageEnum::Angielski->value => 'mailer/registration/eng/remove-student-eng.html',
            default => 'mailer/registration/remove-student.html',
        };
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return [
            'userName' => $this->userName,
            'specialist' => $this->specialist,
            'title' => $this->title,
        ];
    }
}
