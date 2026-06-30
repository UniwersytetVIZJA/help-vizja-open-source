<?php

namespace App\Mailer\Mail\Registration;

use App\Database\Entity\RegisteredStudent;
use App\Database\Entity\Registration;
use App\Database\Entity\Student;
use App\Enum\User\NotificationLanguageEnum;
use App\Mailer\MailContentInterface;
use DateTimeImmutable;

final readonly class Reminder implements MailContentInterface
{
    /**
     * @param string $userName
     * @param string $title
     * @param string $specialist
     * @param string $teamsUrl
     */
    public function __construct(
        private string $userName,
        private string $title,
        private string $specialist,
        private ?string $teamsUrl = null,
        private string $language,
        private DateTimeImmutable $startsAt,
        private DateTimeImmutable $endsAt,
        private ?string $description = null,
        private string $meetingMode,
        private string $locale,
    ) {}

    /**
     * @param Student $student
     * @param Registration $registration
     * @return self
     */
    public static function fromEntity(
        Student $student,
        Registration $registration,
        RegisteredStudent $registeredStudent
    ): self {
        $locale = $student->notificationLanguage ?? NotificationLanguageEnum::Polski->value;

        return new self(
            $student->firstName . ' ' . $student->lastName,
            $locale === NotificationLanguageEnum::Angielski->value
                ? $registration->title->valueEnglish
                : $registration->title->value,
            $registration->specialist->firstName . ' ' . $registration->specialist->lastName,
            $registration->teamsMeetingUrl,
            $registration->language->value,
            $registration->startsAt,
            $registration->endsAt,
            $registration->description,
            $registeredStudent->meetingMode,
            $locale,
        );
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return match ($this->locale) {
            NotificationLanguageEnum::Angielski->value => 'Reminder of consultation: ' . $this->title,
            default => 'Przypomnienie o konsultacji: ' . $this->title,
        };
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return match ($this->locale) {
            NotificationLanguageEnum::Angielski->value => 'mailer/registration/eng/reminder-eng.html',
            default => 'mailer/registration/reminder.html',
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
            'teamsUrl' => $this->teamsUrl,
            'title' => $this->title,
            'language' => $this->language,
            'startsAt' => $this->startsAt,
            'endsAt' => $this->endsAt,
            'description' => $this->description,
            'meetingMode' => $this->meetingMode,
        ];
    }
}
