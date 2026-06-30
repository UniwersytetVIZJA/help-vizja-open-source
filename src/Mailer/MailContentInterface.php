<?php

declare(strict_types=1);

namespace App\Mailer;

/**
 * Interface MailContentInterface
 * @package App\Mailer
 */
interface MailContentInterface
{

    public function getSubject(): string;

    public function getTemplate(): string;

    public function getContext(): array;
}
