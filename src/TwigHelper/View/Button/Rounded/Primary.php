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

/**
 * Class Primary
 * @package App\TwigHelper\View\Button\Rounded
 */
class Primary extends AbstractExtension
{
    /**
     * Primary constructor
     * @param UrlGeneratorInterface $router
     */
    public function __construct(
        private readonly UrlGeneratorInterface $router
    ) {}

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('primary_button_special', $this->primaryButton(...), ['is_safe' => ['html']]),
            new TwigFunction('primary_submit_special', $this->primarySubmit(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param string $label
     * @param string $routeName
     * @param array $routeParams
     * @param array $options
     * @return string
     */
    public function primaryButton(string $label, string $routeName, array $routeParams = [], array $options = []): string
    {
        $href = $this->router->generate($routeName, $routeParams);

        $defaults = [
            'extra_classes' => '',
        ];

        $options = array_merge($defaults, $options);

        $baseClasses = implode(' ', [
            'bg-blue-600 font-medium inline-block px-4 py-2 relative rounded-full text-white text-xs whitespace-nowrap w-fit dark:bg-yellow-400 dark:text-black dark:hover:bg-yellow-300 hover:bg-blue-700 lg:px-5 lg:py-2.5',
            ' ring-offset-3 ring-offset-white focus:ring-3 focus:ring-blue-600 dark:focus:ring-yellow-400 dark:ring-offset-zinc-900'
        ]);

        $class = trim($baseClasses . ' ' . ($options['extra_classes'] ?? ''));

        $labelEsc = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $hrefEsc = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $classEsc = htmlspecialchars($class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<a href="%s" class="%s">%s</a>',
            $hrefEsc,
            $classEsc,
            $labelEsc
        );
    }

    /**
     * @param string $label
     * @param array $options
     * @return string
     */
    public function primarySubmit(string $label, array $options = []): string
    {
        $defaults = [
            'extra_classes' => '',
        ];

        $options = array_merge($defaults, $options);

        $baseClasses = implode(' ', [
            'inline-flex items-center gap-2',
            'rounded-2xl',
            'border border-blue-200/60',
            'px-3 py-2',
            'text-sm font-semibold text-blue-700',
            'shadow-sm transition',
            'hover:bg-blue-100 hover:border-blue-300',
            'dark:bg-blue-900/20 dark:text-blue-200 dark:border-blue-900/40',
            'dark:hover:bg-blue-900/30',
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
