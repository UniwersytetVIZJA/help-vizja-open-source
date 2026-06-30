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

class Delete extends AbstractExtension
{
    public function __construct(
        private UrlGeneratorInterface $router
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('delete_button_special', $this->deleteButton(...), ['is_safe' => ['html'],]),
        ];
    }

    public function deleteButton(string $label, array $options = []): string
    {
        $defaults = [
            'extra_classes' => '',
            'aria_label' => null,
        ];
        $options = array_merge($defaults, $options);

        $baseClasses = implode(' ', [
            'cursor-pointer',
            'inline-flex items-center gap-2',
            'rounded-2xl',
            'border border-red-200/50',
            'bg-red-50',
            'px-3 py-2',
            'text-sm font-semibold text-red-700',
            'shadow-sm transition',
            'hover:bg-red-100 hover:border-red-300',
            'focus-visible:outline-none focus-visible:ring-2',
            'focus-visible:ring-red-400 focus-visible:ring-offset-2',
            'dark:bg-red-900/20 dark:text-red-200 dark:border-red-900/40',
            'dark:hover:bg-red-900/30 dark:focus-visible:ring-red-500',
        ]);

        $class = trim($baseClasses . ' ' . ($options['extra_classes'] ?? ''));

        $labelEsc = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $classEsc = htmlspecialchars($class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $ariaLabel = $options['aria_label'] ?? $label;
        $ariaLabelEsc = htmlspecialchars($ariaLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $iconSvg = '
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
            <path
                d="M9 3a1 1 0 0 0-1 1v1H5.5a1 1 0 1 0 0 2H6v12a2 2 0 0 0 2 2h8a2 2 0 0 0 2-2V7h.5a1 1 0 1 0 0-2H16V4a1 1 0 0 0-1-1H9zm2 3h2V5h-2v1zM9 9a1 1 0 1 1 2 0v8a1 1 0 1 1-2 0V9zm6-1a1 1 0 0 1 1 1v8a1 1 0 1 1-2 0V9a1 1 0 0 1 1-1z" />
        </svg>
    ';

        return sprintf(
            '<Button type="submit" class="%s" aria-label="%s">%s<span>%s</span></Button>',
            $classEsc,
            $ariaLabelEsc,
            $iconSvg,
            $labelEsc
        );
    }

}
