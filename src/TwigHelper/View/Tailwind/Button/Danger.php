<?php

namespace App\TwigHelper\View\Tailwind\Button;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class Danger
 * @package App\TwigHelper\View\Tailwind\Button
 */
class Danger extends AbstractExtension
{
    /**
     * Danger constructor
     * @param RequestStack $requestStack
     */
    public function __construct(private readonly RequestStack $requestStack) {}

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('buttonDanger', [$this, 'extension']),
        ];
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        if ($this->requestStack->getSession()->get('contrast') === 1) {
            return ' bg-red-800 font-medium inline-block px-4 py-2 relative rounded-full text-white text-sm whitespace-nowrap w-fit dark:bg-red-300 dark:text-zinc-900 dark:hover:bg-red-300 hover:bg-red-900 lg:px-5 lg:py-2.5 ';
        }

        return ' bg-red-600 font-medium inline-block px-4 py-2 relative rounded-full text-white text-sm whitespace-nowrap w-fit dark:bg-red-400 dark:text-white dark:hover:bg-red-300 hover:bg-red-700 lg:px-5 lg:py-2.5 ';
    }
}

