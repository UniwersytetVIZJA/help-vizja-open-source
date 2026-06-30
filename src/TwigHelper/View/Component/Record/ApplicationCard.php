<?php

namespace App\TwigHelper\View\Component\Record;

use DateTimeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ApplicationCard extends AbstractExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $router
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('student_application_card', $this->studentApplicationCard(...), ['is_safe' => ['html']]),
        ];
    }

    /**
     * Kafelek wniosku studenta z customowym footerem.
     *
     * @param object $application Encja/DTO z polami:
     *                             - type (obiekt z polem value)
     *                             - status (string)
     *                             - createdAt (DateTimeInterface)
     *                             - id (do href, jeśli chcesz)
     * @param string $footerHtml Gotowy HTML stopki (np. przyciski)
     * @param string $href Docelowy URL (używany w onclick)
     */
    public function studentApplicationCard(object $application, string $footerHtml, string $href): string
    {
        $typeObj = $this->getValue($application, 'type');
        $typeValue = $typeObj ? (string)$this->getValue($typeObj, 'value') : '';

        $status = (string)$this->getValue($application, 'status');
        $createdAt = $this->getValue($application, 'createdAt');

        $createdAtStr = $createdAt instanceof DateTimeInterface
            ? $createdAt->format('d.m.Y H:i')
            : '';

        $statusColors = [
            'Nowy' => 'border-blue-700 text-blue-700',
            'W trakcie' => 'border-zinc-700 text-zinc-700',
            'Do poprawy' => 'border-yellow-700 text-yellow-700',
            'Odrzucony' => 'border-red-700 text-red-700',
            'Zaakceptowany' => 'border-green-700 text-green-700',
        ];

        $statusClass = $statusColors[$status] ?? 'border-blue-700 text-blue-700';

        $typeEsc = htmlspecialchars($typeValue, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $statusEsc = htmlspecialchars($status, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $statusClassEsc = htmlspecialchars($statusClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $createdAtEsc = htmlspecialchars($createdAtStr, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $hrefEsc = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<div class="cursor-pointer mt-6 rounded-2xl border border-zinc-500 dark:border-zinc-700 bg-white dark:bg-zinc-900
                      hover:bg-zinc-100 dark:hover:bg-zinc-800"
                  onclick="window.location=\'%s\'">

                <div class="p-4">
                    <div class="flex items-start">
                        <div class="min-w-0 flex-1 space-y-2">
                            <div class="flex flex-wrap items-center gap-3">
                                <h2 class="text-black text-xl font-semibold dark:text-white">
                                    %s
                                </h2>

                                <span class="inline-flex items-center border rounded-full px-3 py-1 text-sm font-medium dark:text-indigo-300 %s">
                                    %s
                                </span>
                            </div>

                            <p class="mt-3 text-black dark:text-zinc-300">
                                Data utworzenia: %s
                            </p>

                            <br>
                            %s
                        </div>
                    </div>
                </div>
            </div>',
            $hrefEsc,
            $typeEsc,
            $statusClassEsc,
            $statusEsc,
            $createdAtEsc,
            $footerHtml
        );
    }

    private function getValue(mixed $source, string $property): mixed
    {
        if ($source === null) {
            return null;
        }

        if (is_array($source) && array_key_exists($property, $source)) {
            return $source[$property];
        }

        if (is_object($source)) {
            $getter = 'get' . ucfirst($property);
            if (method_exists($source, $getter)) {
                return $source->{$getter}();
            }

            $isser = 'is' . ucfirst($property);
            if (method_exists($source, $isser)) {
                return $source->{$isser}();
            }

            if (property_exists($source, $property)) {
                return $source->{$property};
            }
        }

        return null;
    }
}
