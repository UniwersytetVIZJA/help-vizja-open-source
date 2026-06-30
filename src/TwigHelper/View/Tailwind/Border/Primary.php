<?php

namespace App\TwigHelper\View\Tailwind\Border;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class Primary
 * @package App\TwigHelper\View\Tailwind\Border
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
            new TwigFunction('borderPrimary', [$this, 'extension']),
        ];
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        if ($this->requestStack->getSession()->get('contrast') === 1) {
            return ' border-2 border-zinc-800 dark:border-zinc-500 ';
        }

        return '';
    }
}
