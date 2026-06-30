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

class Secondary extends AbstractExtension
{
    public function __construct(
        private UrlGeneratorInterface $router
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('secondary_button', $this->secondaryButton(...), [
                'is_safe' => ['html'],
            ]),
        ];
    }

    public function secondaryButton(string $label, string $routeName, array $routeParams = [], array $options = []): string
    {
        $href = $this->router->generate($routeName, $routeParams);

        $defaults = [
            'extra_classes' => '',
            'with_container' => false,
        ];
        $options = array_merge($defaults, $options);

        $baseClasses = implode(' ', [
            'bg-zinc-800 font-medium inline-block px-4 py-2 relative rounded-full text-white text-xs whitespace-nowrap w-fit dark:bg-zinc-800 dark:border-2 dark:border-zinc-400 dark:hover:bg-zinc-700 hover:bg-zinc-900 lg:px-5 lg:py-2.5 ring-offset-3 ring-offset-white dark:ring-offset-zinc-900',
            ' ring-offset-3 ring-offset-white focus:ring-3 focus:ring-blue-600 dark:focus:ring-yellow-400 dark:ring-offset-zinc-900'
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
}
