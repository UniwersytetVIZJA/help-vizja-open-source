<?php

namespace App\Mailer\Mail\User;

use App\Database\Entity\User;
use App\Mailer\MailContentInterface;

readonly class CreateUser implements MailContentInterface
{
    /**
     * @param string $userName
     */
    public function __construct(
        private string $userName,
    ) {}

    /**
     * @param User $user
     * @return self
     */
    public static function fromEntity(User $user): self
    {
        return new self(
            $user->firstName . ' ' . $user->lastName,
        );
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return 'Utworzenie konta w panelu administracyjnym';
    }

    /**
     * @return string
     */
    public function getTemplate(): string
    {
        return 'mailer/user/create-user.html';
    }

    /**
     * @return string[]
     */
    public function getContext(): array
    {
        return [
            'userName' => $this->userName,
        ];
    }
}
