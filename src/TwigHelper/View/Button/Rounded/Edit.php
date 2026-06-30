<?php

namespace App\TwigHelper\View\Button\Rounded;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function array_merge;
use function htmlspecialchars;
use function implode;
use function sprintf;
use function trim;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

class Edit extends AbstractExtension
{
    public function __construct(
        private UrlGeneratorInterface $router
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('edit_button_special', $this->editButton(...), ['is_safe' => ['html'],]),
        ];
    }

    public function editButton(string $label, string $routeName, array $routeParams = [], array $options = []): string
    {
        $href = $this->router->generate($routeName, $routeParams);

        $defaults = [
            'extra_classes' => '',
            'aria_label' => null,
        ];
        $options = array_merge($defaults, $options);

        $baseClasses = implode(' ', [
            'cursor-pointer',
            'inline-flex items-center gap-2',
            'rounded-2xl',
            'border border-blue-200/60',
            'bg-blue-50',
            'px-3 py-2',
            'text-sm font-semibold text-blue-700',
            'shadow-sm transition',
            'hover:bg-blue-100 hover:border-blue-300',
            'focus-visible:outline-none focus-visible:ring-2',
            'focus-visible:ring-blue-400 focus-visible:ring-offset-2',
            'dark:bg-blue-900/20 dark:text-blue-200 dark:border-blue-900/40',
            'dark:hover:bg-blue-900/30 dark:focus-visible:ring-blue-600',
        ]);

        $class = trim($baseClasses . ' ' . ($options['extra_classes'] ?? ''));

        $hrefEsc = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $classEsc = htmlspecialchars($class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $labelEsc = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $ariaLabel = $options['aria_label'] ?? $label;
        $ariaEsc = htmlspecialchars($ariaLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $iconSvg = '
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM21.41 6.34a1.25 1.25 0 0 0 0-1.77l-2.98-2.98a1.25 1.25 0 0 0-1.77 0l-1.83 1.83 3.75 3.75 1.83-1.83z" />
        </svg>
    ';

        return sprintf(
            '<a href="%s" class="%s" aria-label="%s">%s<span>%s</span></a>',
            $hrefEsc,
            $classEsc,
            $ariaEsc,
            $iconSvg,
            $labelEsc
        );
    }

}
