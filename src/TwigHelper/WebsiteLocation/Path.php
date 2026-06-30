<?php

namespace App\TwigHelper\WebsiteLocation;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Path extends AbstractExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $router, private readonly TranslatorInterface $translator
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('website_path', $this->renderBreadcrumb(...), ['is_safe' => ['html'],]),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * Każdy element:
     *  - 'label' (string, wymagany)
     *  - 'route' (string, opcjonalny) LUB 'url' (string)
     *  - 'route_params' (array, opcjonalny)
     */
    public function renderBreadcrumb(array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $lis = [];
        $lastIndex = \count($items) - 1;

        foreach ($items as $index => $item) {
            $label = (string)($item['label'] ?? '');
            $labelEsc = htmlspecialchars($label, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            $href = null;
            if (!empty($item['route'])) {
                $params = $item['route_params'] ?? [];
                $href = $this->router->generate($item['route'], $params);
            } else if (!empty($item['url'])) {
                $href = (string)$item['url'];
            }

            $hrefAttr = $href !== null
                ? htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                : '';

            if ($index === 0) {
                $lis[] = sprintf(
                    '<li class="inline-flex items-center">
                        <a href="%s" class="inline-flex items-center text-sm font-medium text-zinc-700 hover:text-primary-600 dark:text-zinc-400 dark:hover:text-white">
                            <svg class="me-2 h-4 w-4" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none" viewBox="0 0 24 24">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="m4 12 8-8 8 8M6 10.5V19a1 1 0 0 0 1 1h3v-3a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v3h3a1 1 0 0 0 1-1v-8.5" />
                            </svg>
                            %s
                        </a>
                    </li>',
                    $hrefAttr,
                    $labelEsc
                );
                continue;
            }

            $arrowSvg = '
                <svg class="mx-1 h-4 w-4 text-zinc-400 rtl:rotate-180" aria-hidden="true"
                     xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="none"
                     viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7" />
                </svg>
            ';

            if ($index === $lastIndex || $href === null) {
                $lis[] = sprintf(
                    '<li>
                        <div class="flex items-center">
                            %s
                            <span class="ms-1 text-sm font-medium text-zinc-500 dark:text-zinc-400 md:ms-2" aria-current="page">%s</span>
                        </div>
                    </li>',
                    $arrowSvg,
                    $labelEsc
                );
            } else {
                $lis[] = sprintf(
                    '<li>
                        <div class="flex items-center">
                            %s
                            <a href="%s"
                               class="ms-1 text-sm font-medium text-zinc-700 hover:text-primary-600 dark:text-zinc-400 dark:hover:text-white md:ms-2">
                               %s
                            </a>
                        </div>
                    </li>',
                    $arrowSvg,
                    $hrefAttr,
                    $labelEsc
                );
            }
        }

        $ol = implode("\n", $lis);

        return sprintf(
            '<nav class="mb-4 flex" aria-label="%s">
        <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">
            %s
        </ol>
    </nav>',
            htmlspecialchars(
                $this->translator->trans('Ścieżka nawigacji'),
                ENT_QUOTES | ENT_SUBSTITUTE,
                'UTF-8'
            ),
            $ol
        );
    }
}
