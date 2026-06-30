<?php

namespace App\TwigHelper\View\Component\Record;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class InventoryCard extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('inventory_card', $this->inventoryCard(...), ['is_safe' => ['html'],]),
        ];
    }

    /**
     * Renderuje kartę słownikową/inventory.
     *
     * @param string $title Tytuł (np. item.value)
     * @param bool|null $isActive Opcjonalny flag aktywności; jeśli null – badge nie jest pokazywany
     * @param string|null $description Opcjonalny opis
     * @param string $actionsHtml Gotowy HTML z przyciskami/akcjami (np. z helperów buttonów)
     */
    public function inventoryCard(
        string $title,
        string $bodyHtml,
        string $actionsHtml = '',
        array $options = []
    ): string {
        $defaults = [
            'extra_classes' => '',
            'header_right_html' => null,
        ];
        $options = array_merge($defaults, $options);

        $titleEsc = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $extraClass = trim($options['extra_classes']);

        $headerRightHtml = $options['header_right_html'] ?? '';

        $articleClasses = trim(
            'rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:shadow-md ' .
            'dark:border-zinc-700 dark:bg-zinc-800 ' .
            $extraClass
        );

        return sprintf(
            '<article class="%s" data-card>
                <header class="mb-3 flex flex-col items-start gap-2 sm:flex-row sm:justify-between sm:gap-3">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">%s</h3>
                    %s
                </header>
                %s
                %s
            </article>',
            $articleClasses,
            $titleEsc,
            $headerRightHtml,
            $bodyHtml,
            $actionsHtml
        );
    }
}
