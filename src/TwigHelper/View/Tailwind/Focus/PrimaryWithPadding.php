<?php

namespace App\TwigHelper\View\Tailwind\Focus;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Class Primary
 * @package App\TwigHelper\View\Tailwind\Focus
 */
class PrimaryWithPadding extends AbstractExtension
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
            new TwigFunction('focusPrimaryWithPadding', $this->extension(...)),
            new TwigFunction('peerFocusPrimaryWithPadding', $this->peerExtension(...)),
        ];
    }

    /**
     * @return string
     */
    public function extension(): string
    {
        if ($this->requestStack->getSession()->get('contrast') === 1) {
            return ' ring-offset-3 ring-offset-white focus:ring-3 focus:ring-blue-800 dark:focus:ring-yellow-400 dark:ring-offset-zinc-900 ';
        }

        return '
        focus-visible:outline-3
        focus-visible:outline-offset-2
        focus-visible:outline-blue-600
        dark:focus-visible:outline-yellow-400
        focus-visible:ring-3
        focus-visible:ring-blue-600
        dark:focus-visible:ring-yellow-400
        ring-offset-3
        ring-offset-white
        dark:ring-offset-zinc-900
        ';
    }

    public function peerExtension(): string
    {
        if ($this->requestStack->getSession()->get('contrast') === 1) {
            return '
            peer-focus:ring-3
            peer-focus:ring-blue-600
            dark:peer-focus:ring-yellow-400
            peer-focus:ring-offset-3
            peer-focus:ring-offset-white
            dark:peer-focus:ring-offset-zinc-900
        ';
        }

        return '
        peer-focus-visible:outline-3
        peer-focus-visible:outline-offset-2
        peer-focus-visible:outline-blue-600
        dark:peer-focus-visible:outline-yellow-400

        peer-focus-visible:ring-3
        peer-focus-visible:ring-blue-600
        dark:peer-focus-visible:ring-yellow-400

        peer-focus-visible:ring-offset-3
        peer-focus-visible:ring-offset-white
        dark:peer-focus-visible:ring-offset-zinc-900
    ';
    }
}
