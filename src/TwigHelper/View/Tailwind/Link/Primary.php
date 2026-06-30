<?php

namespace App\TwigHelper\View\Tailwind\Link;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class Primary
 * @package App\TwigHelper\View\Tailwind\Link
 */
class Primary extends AbstractExtension
{
    /**
     * Primary constructor
     * @param RequestStack $requestStack
     */
    public function __construct(private readonly RequestStack $requestStack) {}

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('linkPrimary', [$this, 'extension']),
        ];
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        if ($this->requestStack->getSession()->get('contrast') === 1) {
            return ' font-medium inline-flex items-center p-2 rounded-lg text-blue-800 underline hover:text-blue-900 dark:text-yellow-400 dark:hover:text-yellow-300 ';
        }

        return ' font-medium inline-flex items-center rounded-lg text-blue-600 underline hover:text-blue-700 dark:text-yellow-400 dark:hover:text-yellow-300 ';
    }
}
