<?php

namespace App\TwigHelper\View\Component\Record;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EmptySpace extends AbstractExtension
{
    public function __construct(
        private UrlGeneratorInterface $router
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('empty_state', $this->emptyState(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * Renderuje widok pustego rekordu.
     *
     * @param string $title Tytuł (np. "Brak wniosków")
     * @param string $description Opis (np. "Nie masz jeszcze żadnych wniosków...")
     * @param array $options Dodatkowe opcje:
     *                            - action_route        => string|null (nazwa routa dla CTA)
     *                            - action_route_params => array (parametry routa)
     *                            - action_label        => string (etykieta CTA)
     *                            - action_classes      => string (dodatkowe / własne klasy dla linka)
     */
    public function emptyState(string $title, string $description, array $options = []): string
    {
        $defaults = [
            'action_route' => null,
            'action_route_params' => [],
            'action_label' => null,
            'action_classes' => '',
        ];
        $options = array_merge($defaults, $options);

        $titleEsc = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $descriptionEsc = htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $actionHtml = '';
        if ($options['action_route']) {
            $href = $this->router->generate($options['action_route'], $options['action_route_params'] ?? []);
            $hrefEsc = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $label = $options['action_label'] ?? 'Utwórz nowy rekord';
            $labelEsc = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $baseActionClasses = implode(' ', [
                '
                cursor-pointer
                inline-block w-fit whitespace-nowrap rounded-full border-3
                border-blue-600 bg-blue-600
                px-4 py-2 text-sm font-medium
                text-white transition-colors duration-200
                hover:border-blue-700 hover:bg-blue-700
                

                dark:border-yellow-400
                dark:bg-yellow-400
                dark:text-zinc-900
                dark:hover:border-yellow-300
                dark:hover:bg-yellow-300

                forced-colors:border-[ButtonText]
                forced-colors:bg-[ButtonFace]
                forced-colors:text-[ButtonText]
                forced-colors:hover:bg-[Highlight]
                forced-colors:hover:text-[HighlightText]

                lg:px-5 lg:py-2.5
                ',
                'focus-visible:outline-3 focus-visible:outline-offset-2 focus-visible:outline-blue-600 dark:focus-visible:outline-yellow-400',
            ]);

            $actionClasses = trim($baseActionClasses . ' ' . ($options['action_classes'] ?? ''));
            $actionClassesEsc = htmlspecialchars($actionClasses, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $actionHtml = sprintf(
                '<h2 class="mt-5 flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="%s" class="%s">%s</a>
                </h2>',
                $hrefEsc,
                $actionClassesEsc,
                $labelEsc
            );
        }

        $html = sprintf(
            '<div class="rounded-2xl border-2 border-dashed border-zinc-300 dark:border-zinc-700 bg-white/60 dark:bg-zinc-900/60">
                <div class="px-6 py-8 sm:px-8 sm:py-10 text-center">
                    <div class="mx-auto mb-4 h-12 w-12 rounded-full bg-indigo-100 dark:bg-indigo-900/40 grid place-content-center">
                        <svg class="w-6 h-6 text-zinc-800 dark:text-white" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.529 9.988a2.502 2.502 0 1 1 5 .191A2.441 2.441 0 0 1 12 12.582V14m-.01 3.008H12M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>

                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">%s</h2>
                    <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">%s</p>
                    %s
                </div>
            </div>',
            $titleEsc,
            $descriptionEsc,
            $actionHtml
        );

        return $html;
    }
}
