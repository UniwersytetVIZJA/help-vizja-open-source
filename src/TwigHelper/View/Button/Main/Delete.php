<?php

namespace App\TwigHelper\View\Button\Main;

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
            new TwigFunction('primary_delete_button', $this->primaryDeleteButton(...), ['is_safe' => ['html'],]),
            new TwigFunction('primary_delete_submit_button', $this->primarySubmit(...), ['is_safe' => ['html'],]),
        ];
    }

    public function primaryDeleteButton(string $label, string $routeName, array $routeParams = [], array $options = []): string
    {
        $href = $this->router->generate($routeName, $routeParams);

        $defaults = [
            'extra_classes' => '',
            'with_container' => false,
        ];
        $options = array_merge($defaults, $options);

        $baseClasses = implode(' ', [
            'cursor-pointer',
            'flex w-full items-center justify-center',
            'rounded-lg',
            'border border-red-700 bg-red-700',
            'px-5 py-2.5',
            'text-sm font-medium text-white',
            'hover:border-red-800 hover:bg-red-800',
            ' focus:ring-4 focus:ring-red-300',
            'dark:border-red-600 dark:bg-red-600',
            'dark:hover:border-red-700 dark:hover:bg-red-700',
            'dark:focus:ring-red-800',
            'sm:w-auto',
        ]);

        $class = trim($baseClasses . ' ' . ($options['extra_classes'] ?? ''));

        $labelEscaped = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $hrefEscaped = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $classEscaped = htmlspecialchars($class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $buttonHtml = sprintf(
            '<a href="%s" class="%s">%s</a>',
            $hrefEscaped,
            $classEscaped,
            $labelEscaped
        );

        if ($options['with_container']) {
            return sprintf(
                '<div class="mt-4 flex flex-col sm:flex-row sm:items-start gap-4">%s</div>',
                $buttonHtml
            );
        }

        return $buttonHtml;
    }

    public function primarySubmit(string $label, array $options = []): string
    {
        $defaults = [
            'extra_classes' => '',
        ];
        $options = array_merge($defaults, $options);

        $baseClasses = implode(' ', [
            'cursor-pointer',
            'inline-flex items-center justify-center',
            'text-sm font-medium',
            'text-white',
            'bg-red-600',
            'rounded-lg',
            'px-3 py-1.5',
            'border border-transparent',
            'hover:bg-red-700',
            '',
            'focus:ring-4 focus:ring-red-300',
            'transition-colors',
        ]);

        $class = trim($baseClasses . ' ' . ($options['extra_classes'] ?? ''));

        $labelEscaped = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $classEscaped = htmlspecialchars($class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<Button type="submit" class="%s">%s</Button>',
            $classEscaped,
            $labelEscaped
        );
    }
}
