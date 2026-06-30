<?php

namespace App\Mailer\Mail\OfficeRegistration;

use App\Database\Entity\Application;
use App\Database\Entity\OfficeRegistration;
use App\Database\Entity\Student;
use App\Enum\User\NotificationLanguageEnum;
use App\Mailer\MailContentInterface;
use DateTimeImmutable;
use GuzzleHttp\HandlerStack;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class NewOfficeRegistration implements MailContentInterface {

    public function __construct(
        private string $userName,
        private DateTimeImmutable $startAt,
        private DateTimeImmutable $endAt,
        private string $meetingMode,
        private string $locale,
    ) {}

    public static function fromEntity(Student $student, OfficeRegistration $registration): self
    {
        $registeredStudent = $registration->registeredStudents
            ->filter(fn($rs) => $rs->student === $student)
            ->first();

        return new self(
            $student->firstName . ' ' . $student->lastName,
            $registration->startAt,
            $registration->endAt,
            $registeredStudent->meetingMode,
            $student->notificationLanguage ?? NotificationLanguageEnum::Polski->value,
        );
    }

    public function getSubject(): string
    {
        return match ($this->locale){
            NotificationLanguageEnum::Angielski->value => 'Appointment Confirmation with the Office for Students with Disabilities',
            default => 'Potwierdzenie zapisu na wizytę w Biurze ds. Osób z Niepełnosprawnościami',
        };
    }

    public function getTemplate(): string
    {
        return match ($this->locale) {
            NotificationLanguageEnum::Angielski->value => 'mailer/office-registration/eng/new-registration-eng.html',
            default => 'mailer/office-registration/new-registration.html',
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
