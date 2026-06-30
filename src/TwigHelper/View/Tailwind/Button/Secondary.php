<?php

namespace App\TwigHelper\View\Tailwind\Button;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class Secondary
 * @package App\TwigHelper\View\Tailwind\Button
 */
class Secondary extends AbstractExtension
{
    /**
     * Secondary constructor
     * @param RequestStack $requestStack
     */
    public function __construct(private readonly RequestStack $requestStack) {}

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('buttonSecondary', [$this, 'extension']),
        ];
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        if ($this->requestStack->getSession()->get('contrast') === 1) {
            return ' bg-zinc-800 font-medium inline-block px-4 py-2 relative rounded-full text-white text-sm whitespace-nowrap w-fit dark:bg-zinc-800 dark:border-2 dark:border-zinc-400 dark:hover:bg-zinc-700 hover:bg-zinc-900 lg:px-5 lg:py-2.5 ';
        }

        return '
                cursor-pointer
                inline-block w-fit whitespace-nowrap rounded-full border-3
                border-blue-600 bg-white px-4 py-2 text-sm font-medium
                text-blue-600 transition-colors duration-200
                hover:bg-blue-600 hover:text-white
                

                dark:border-yellow-400
                dark:bg-zinc-800
                dark:text-yellow-400
                dark:hover:bg-yellow-400
                dark:hover:text-zinc-900

                forced-colors:border-[ButtonText]
                forced-colors:bg-[Canvas]
                forced-colors:text-[ButtonText]
                forced-colors:hover:bg-[Highlight]
                forced-colors:hover:text-[HighlightText]

                lg:px-5 lg:py-2.5
                ';
    }
}
