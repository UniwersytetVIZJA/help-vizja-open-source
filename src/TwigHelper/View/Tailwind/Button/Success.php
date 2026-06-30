<?php

namespace App\TwigHelper\View\Tailwind\Button;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class Success
 * @package App\TwigHelper\View\Tailwind\Button
 */
class Success extends AbstractExtension
{
    /**
     * Success constructor
     * @param RequestStack $requestStack
     */
    public function __construct(private readonly RequestStack $requestStack) {}

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('buttonSuccess', [$this, 'extension']),
        ];
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        if ($this->requestStack->getSession()->get('contrast') === 1) {
            return ' bg-emerald-800 font-medium inline-block px-4 py-2 relative rounded-full text-white text-sm whitespace-nowrap w-fit dark:bg-emerald-300 dark:text-zinc-900 dark:hover:bg-emerald-300 hover:bg-emerald-900 lg:px-5 lg:py-2.5 ';
        }

        return ' bg-emerald-600 font-medium inline-block px-4 py-2 relative rounded-full text-white text-sm whitespace-nowrap w-fit dark:bg-emerald-400 dark:text-white dark:hover:bg-emerald-300 hover:bg-emerald-700 lg:px-5 lg:py-2.5 ';
    }
}
