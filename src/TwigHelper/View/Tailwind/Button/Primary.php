<?php

namespace App\TwigHelper\View\Tailwind\Button;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class Primary
 * @package App\TwigHelper\View\Tailwind\Button
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
            new TwigFunction('buttonPrimary', [$this, 'extension']),
        ];
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        if ($this->requestStack->getSession()->get('contrast') === 1) {
            return ' bg-blue-800 font-medium inline-block px-4 py-2 relative rounded-full text-white text-sm whitespace-nowrap w-fit dark:bg-yellow-400 dark:text-black dark:hover:bg-yellow-300 hover:bg-blue-900 lg:px-5 lg:py-2.5 ';
        }

        return '
                cursor-pointer
                inline-block w-fit whitespace-nowrap rounded-full border-3
                border-blue-600 bg-blue-600
                px-4 py-2 text-sm font-medium
                text-white transition-colors duration-200
                hover:border-blue-700 hover:bg-blue-700
                

                dark:border-yellow-400
                dark:bg-yellow-400
                dark:text-zinc-900
                dark:hover:border-yellow-300
                dark:hover:bg-yellow-300

                forced-colors:border-[ButtonText]
                forced-colors:bg-[ButtonFace]
                forced-colors:text-[ButtonText]
                forced-colors:hover:bg-[Highlight]
                forced-colors:hover:text-[HighlightText]

                lg:px-5 lg:py-2.5
                ';
    }
}
