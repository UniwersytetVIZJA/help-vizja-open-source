<?php

namespace App\TwigHelper\View\Tailwind\Border;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class Navigation
 * @package App\TwigHelper\View\Tailwind\Border
 */
class Navigation extends AbstractExtension
{
    /**
     * Navigation constructor
     * @param RequestStack $requestStack
     */
    public function __construct(private readonly RequestStack $requestStack) {}

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('borderNavigation', [$this, 'extension']),
        ];
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        if ($this->requestStack->getSession()->get('contrast') === 1) {
            return ' border-b-2 border-t-2 border-zinc-800 dark:border-zinc-400 ';
        }

        return '';
    }
}
