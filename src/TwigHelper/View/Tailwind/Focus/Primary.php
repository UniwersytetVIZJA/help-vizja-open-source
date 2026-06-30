<?php

namespace App\TwigHelper\View\Tailwind\Focus;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class Primary
 * @package App\TwigHelper\View\Tailwind\Focus
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
            new TwigFunction('focusPrimary', [$this, 'extension']),
        ];
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        if ($this->requestStack->getSession()->get('contrast') === 1) {
            return '  focu  s:ring-3 focus:ring-blue-600 dark:focus:ring-yellow-400 ';
        }

        return ' focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:focus-visible:outline-yellow-400';
    }
}
