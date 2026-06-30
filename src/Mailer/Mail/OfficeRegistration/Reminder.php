<?php

namespace App\Mailer\Mail\OfficeRegistration;

use App\Database\Entity\OfficeRegistration;
use App\Database\Entity\Student;
use App\Enum\User\NotificationLanguageEnum;
use App\Mailer\MailContentInterface;
use DateTimeImmutable;

class Reminder implements MailContentInterface
{

    public function __construct(
        private string $userName,
        private DateTimeImmutable $startAt,
        private DateTimeImmutable $endAt,
        private ?string $meetingMode = null,
        private string $locale,
    ) {}

    public static function fromEntity(Student $student, OfficeRegistration $registration): self
    {
        $registeredStudent = $registration->registeredStudents
            ->filter(fn($rs) => $rs->student?->id === $student->id)
            ->first();

        $meetingMode = $registeredStudent
            ? $registeredStudent->meetingMode
            : null;

        return new self(

            $student->firstName . ' ' . $student->lastName,
            $registration->startAt,
            $registration->endAt,
            $meetingMode,
            $student->notificationLanguage ?? NotificationLanguageEnum::Polski->value,

        );
    }

    public function getSubject(): string
    {
        return match ($this->locale) {
            NotificationLanguageEnum::Angielski->value => 'Reminder of an Appointment with the Office for Students with Disabilities',
            default => 'Przypomnienie o wizycie w Biurze ds. Osób z Niepełnosprawnościami'
        };
    }

    public function getTemplate(): string
    {
        return match ($this->locale) {
            NotificationLanguageEnum::Angielski->value => 'mailer/office-registration/eng/reminder-eng.html',
            default => 'mailer/office-registration/reminder.html',
        };
    }

    public function getContext(): array
    {
        return [
            'userName' => $this->userName,
            'startAt' => $this->startAt,
            'endAt' => $this->endAt,
            'meetingMode' => match ($this->meetingMode) {
                'Spotkanie stacjonarne' => 'Spotkanie w siedzibie biura (ul. Okopowa 59, Warszawa)',
                'Spotkanie online' => 'Spotkanie online (MS Teams)',
                default => $this->meetingMode,
            },
        ];
    }
}
