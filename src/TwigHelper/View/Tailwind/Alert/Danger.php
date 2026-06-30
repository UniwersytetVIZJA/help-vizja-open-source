<?php

namespace App\TwigHelper\View\Tailwind\Alert;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class Danger
 * @package App\TwigHelper\View\Tailwind\Alert
 */
class Danger extends AbstractExtension
{
    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('alertDanger', [$this, 'extension']),
        ];
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        return 'bg-red-50 p-4 rounded-lg space-y-1 text-sm text-red-950 dark:bg-zinc-800 dark:text-red-300';
    }
}
