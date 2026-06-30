<?php

namespace App\TwigHelper\View\Component\Record;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class SettingCard extends AbstractExtension
{
    public function __construct(
        private UrlGeneratorInterface $router
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('dashboard_link_card', $this->dashboardLinkCard(...), ['is_safe' => ['html'],]),
        ];
    }

    /**
     * Kafelek na dashboardzie, linkujący do sekcji.
     *
     * @param string $title Tytuł (np. "Studenci")
     * @param string $description Opis pod tytułem
     * @param string $routeName Nazwa routa do wygenerowania href
     * @param array $routeParams Parametry routa
     * @param array $options Dodatkowe opcje:
     *                            - 'icon'        => własny SVG (string, opcjonalnie)
     *                            - 'accent'      => kolor akcentu ('emerald', 'blue', ... przyszłościowo)
     *                            - 'open_label'  => tekst "Otwórz"
     */
    public function dashboardLinkCard(
        string $title,
        string $description,
        string $routeName,
        array $routeParams = [],
        array $options = []
    ): string {
        $defaults = [
            'icon_html' => null,
            'accent' => 'emerald',
            'open_label' => 'Otwórz',
        ];
        $options = array_merge($defaults, $options);

        $href = $this->router->generate($routeName, $routeParams);

        $titleEsc = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $descriptionEsc = htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $hrefEsc = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $openLabelEsc = htmlspecialchars($options['open_label'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        switch ($options['accent']) {
            case 'purple':
                $accentBg = 'bg-purple-100 dark:bg-purple-900/40';
                $accentText = 'text-purple-700 dark:text-purple-300';
                $accentLink = 'text-purple-700 dark:text-purple-300';
                break;
            case 'amber':
                $accentBg = 'bg-amber-100 dark:bg-amber-900/40';
                $accentText = 'text-amber-700 dark:text-amber-300';
                $accentLink = 'text-amber-700 dark:text-amber-300';
                break;
            case 'fuchsia':
                $accentBg = 'bg-fuchsia-100 dark:bg-fuchsia-800/40';
                $accentText = 'text-fuchsia-900 dark:text-fuchsia-400';
                $accentLink = 'text-fuchsia-900 dark:text-fuchsia-300';
                break;
            case 'sky':
                $accentBg = 'bg-sky-100 dark:bg-sky-800/40';
                $accentText = 'text-sky-500 dark:text-sky-400';
                $accentLink = 'text-sky-500 dark:text-sky-300';
                break;
            case 'emerald':
            default:
                $accentBg = 'bg-emerald-100 dark:bg-emerald-900/40';
                $accentText = 'text-emerald-700 dark:text-emerald-300';
                $accentLink = 'text-emerald-700 dark:text-emerald-300';
                break;
        }

        $iconHtml = $options['icon_html'] ?? '
            <svg class="h-5 w-5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                 width="24" height="24" fill="currentColor" viewBox="0 0 24 24">
                <path d="M4 5a2 2 0 0 1 2-2h4v4H4V5Zm0 6a2 2 0 0 1 2-2h4v6H6a2 2 0 0 1-2-2v-2Zm8-4V3h4a2 2 0 0 1 2 2v2h-6Zm6 2h-6v6h6v-4a2 2 0 0 0-2-2Zm-6 8v4h-4a2 2 0 0 1-2-2v-2h6Zm2 4v-4h4a2 2 0 0 1 2 2v2h-6Z"/>
            </svg>
        ';

        return sprintf(
            '<a href="%s" data-card
                class="group rounded-2xl border border-zinc-300 bg-white p-5 transition hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700">
                <div class="flex items-start justify-between">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-content-center rounded-xl %s %s">
                            %s
                        </span>
                        <div>
                            <h2 class="text-base font-semibold text-zinc-900 dark:text-white">%s</h2>
                            <p class="text-sm text-zinc-600 dark:text-zinc-300">%s</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 inline-flex items-center text-sm font-medium %s group-hover:underline">
                    %s
                </div>
            </a>',
            $hrefEsc,
            $accentBg,
            $accentText,
            $iconHtml,
            $titleEsc,
            $descriptionEsc,
            $accentLink,
            $openLabelEsc
        );
    }
}
