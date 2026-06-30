<?php

namespace App\TwigHelper\View\Button\Main;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use twig\TwigFunction;

class Primary extends AbstractExtension
{
    public function __construct(
        private UrlGeneratorInterface $router
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('primary_button', $this->primaryButton(...), ['is_safe' => ['html'],]),
            new \Twig\TwigFunction('primary_submit', $this->primarySubmit(...), ['is_safe' => ['html']]),
        ];
    }

    public function primaryButton(string $label, string $routeName, array $routeParams = [], array $options = []): string
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
            'border border-primary-700 bg-primary-700',
            'px-5 py-2.5',
            'text-sm font-medium text-white',
            'hover:border-primary-800 hover:bg-primary-800',
            ' focus:ring-4 focus:ring-primary-300',
            'dark:border-primary-600 dark:bg-primary-600',
            'dark:hover:border-primary-700 dark:hover:bg-primary-700',
            'dark:focus:ring-primary-800',
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
            'cursor-pointer bg-blue-600 font-medium inline-block px-4 py-2 relative rounded-full text-white text-xs whitespace-nowrap w-fit dark:bg-yellow-400 dark:text-black dark:hover:bg-yellow-300 hover:bg-blue-700 lg:px-5 lg:py-2.5',
            ' ring-offset-3 ring-offset-white focus:ring-3 focus:ring-blue-600 dark:focus:ring-yellow-400 dark:ring-offset-zinc-900'
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
