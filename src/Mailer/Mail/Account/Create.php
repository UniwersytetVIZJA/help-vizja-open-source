<?php

namespace App\Mailer\Mail\Account;

use App\Database\Entity\Student;
use App\Enum\User\NotificationLanguageEnum;
use App\Mailer\MailContentInterface;

readonly class Create implements MailContentInterface
{

    /**
     * @param string $userName
     * @param string $applicationId
     * @param string $comment
     */
    public function __construct(
        private string $userName,
        private string $locale,
    ) {}

    /**
     * @param Student $student
     * @return self
     */
    public static function fromEntity(Student $student): self
    {
        return new self(
            $student->firstName . ' ' . $student->lastName,
            $student->notificationLanguage ?? NotificationLanguageEnum::Polski->value,
        );
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return match ($this->locale){
            NotificationLanguageEnum::Angielski->value => 'Confirmation of account creation on the VIZJA University support website',
            default => 'Potwierdzenie założenia konta na stronie wsparcia Uniwersytetu VIZJA',
        };
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return match ($this->locale) {
            NotificationLanguageEnum::Angielski->value => 'mailer/account/eng/create-eng.html',
            default => 'mailer/account/create.html',
        };
    }

    /**
     * @return array
     */
    public function getContext(): array
    {
        return [
            'userName' => $this->userName,
        ];
    }
}
