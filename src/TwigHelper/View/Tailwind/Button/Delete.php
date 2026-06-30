<?php

namespace App\TwigHelper\View\Tailwind\Button;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Delete extends AbstractExtension
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
            new TwigFunction('buttonDelete', $this->extension(...)),
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
    inline-block
    w-fit
    whitespace-nowrap
    rounded-full
    border-3
    border-red-500
    bg-white
    px-4
    py-2
    text-sm
    font-medium
    text-red-600
    transition-colors
    hover:bg-red-500
    hover:text-white
    
    focus:ring-2
    focus:ring-red-500
    focus:ring-offset-2

    dark:border-red-300
    dark:bg-zinc-800
    dark:text-red-300
    dark:hover:border-red-200
    dark:hover:bg-red-300
    dark:hover:text-black
    dark:focus:ring-red-300
    dark:focus:ring-offset-zinc-900

    forced-colors:border-[ButtonText]
    forced-colors:bg-[Canvas]
    forced-colors:text-[ButtonText]

    lg:px-5
    lg:py-2.5
';    }
}
