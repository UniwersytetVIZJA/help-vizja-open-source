<?php

namespace App\TwigHelper\View\String;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class ShortenEmail
 * @package App\TwigHelper\View\String
 */
class ShortenEmail extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('shortenEmail', $this->extension(...)),
        ];
    }

    /**
     * @param string $email
     * @return string
     */
    public function extension(string $email): string
    {
        if (!str_contains($email, '@')) {
            return $email;
        }

        [$localPart, $domain] = explode('@', $email, 2);

        if (strlen($localPart) <= 10) {
            return $email;
        }

        $start = substr($localPart, 0, 5);

        return $start . '...' . '@' . $domain;
    }
}
