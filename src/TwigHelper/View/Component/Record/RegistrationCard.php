<?php

namespace App\TwigHelper\View\Component\Record;

use App\Database\Entity\Dictionary\Item;
use DateTimeInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RegistrationCard extends AbstractExtension
{
    public function __construct(
        private readonly UrlGeneratorInterface $router
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('registration_card', $this->registrationArchiveCard(...), ['is_safe' => ['html']]),];
    }

    /**
     * Renderuje kafelek archiwalnego zapisu wg przesłanego Twiga.
     *
     * @param object $registration Encja/DTO z polami:
     *                              - title
     *                              - capacity (int|null)
     *                              - registered (int|null)
     *                              - registeredStudents (iterable|null)
     *                              - specialist (obiekt z firstName, lastName)
     *                              - startsAt (DateTimeInterface)
     *                              - endsAt   (DateTimeInterface)
     */
    public function registrationArchiveCard(object $registration, string $footerHtml): string
    {
        $titleItem = $this->getValue($registration, 'title');
        $capacity = $this->getValue($registration, 'capacity');
        $registered = $this->getValue($registration, 'registered');
        $regStudents = $this->getValue($registration, 'registeredStudents');
        $specialist = $this->getValue($registration, 'specialist');
        $startsAt = $this->getValue($registration, 'startsAt');
        $endsAt = $this->getValue($registration, 'endsAt');

        $title = $titleItem instanceof Item ? $titleItem->value : (string)$titleItem;

        $firstName = $specialist ? (string)$this->getValue($specialist, 'firstName') : '';
        $lastName = $specialist ? (string)$this->getValue($specialist, 'lastName') : '';
        $specialistName = trim($firstName . ' ' . $lastName);

        $startsDate = $startsAt instanceof DateTimeInterface ? $startsAt->format('d.m.Y') : '';
        $startsTime = $startsAt instanceof DateTimeInterface ? $startsAt->format('H:i') : '';
        $endsTime = $endsAt instanceof DateTimeInterface ? $endsAt->format('H:i') : '';

        $total = is_numeric($capacity) ? (int)$capacity : 0;

        if ($registered !== null && is_numeric($registered)) {
            $used = (int)$registered;
        } else if (is_iterable($regStudents)) {
            $used = is_countable($regStudents) ? count($regStudents) : iterator_count($regStudents);
        } else {
            $used = 0;
        }

        $full = $total > 0 && $used >= $total;

        $statusLabel = $full ? 'Zapisy zamknięte' : 'Zapisy otwarte';
        $chipClasses = $full
            ? 'bg-red-500 text-white dark:bg-red-500 dark:text-white'
            : 'bg-green-500 text-white dark:bg-green-500 dark:text-white';

        if ($total > 0) {
            $pct = (int)floor(($used / $total) * 100);
        } else {
            $pct = 0;
        }
        $pctClamped = max(0, min(100, $pct));

        if ($pctClamped === 100) {
            $barClass = 'bg-red-500';
        } else if ($pctClamped >= 60) {
            $barClass = 'bg-orange-300';
        } else {
            $barClass = 'bg-green-500';
        }

        $titleEsc = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $specNameEsc = htmlspecialchars($specialistName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $startsDateEsc = htmlspecialchars($startsDate, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $startsTimeEsc = htmlspecialchars($startsTime, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $endsTimeEsc = htmlspecialchars($endsTime, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $statusLabelEsc = htmlspecialchars($statusLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $chipClassesEsc = htmlspecialchars($chipClasses, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $barClassEsc = htmlspecialchars($barClass, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $totalEsc = htmlspecialchars((string)$total, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $usedEsc = htmlspecialchars((string)$used, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $pctEsc = htmlspecialchars((string)$pctClamped, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $html = sprintf(
            '<article class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800">
                <header class="mb-3 flex items-start justify-between gap-3">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">%s</h2>
                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium %s">
                        %s
                    </span>
                </header>

                <dl class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Specjalista</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">%s</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Data</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">%s</dd>
                    </div>
                    <div class="flex items-center justify-between">
                        <dt class="text-gray-500 dark:text-gray-400">Godziny</dt>
                        <dd class="font-medium text-gray-900 dark:text-gray-100">%s–%s</dd>
                    </div>
                </dl>

                <div class="mt-3 space-y-2">
                    <div class="flex items-baseline justify-between text-sm">
                        <span class="text-gray-600 dark:text-gray-400">
                            Zapisanych:
                            <span class="font-medium text-gray-900 dark:text-white">%s</span> / %s
                        </span>
                        <span class="font-medium text-gray-900 dark:text-white">%s%%</span>
                    </div>

                    <div class="h-3 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-zinc-700"
                         role="progressbar"
                         aria-label="Zapełnienie zapisów"
                         aria-valuemin="0"
                         aria-valuemax="%s"
                         aria-valuenow="%s">
                        <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-zinc-700">
                            <div class="%s h-2.5 rounded-full" style="width: %s%%"></div>
                        </div>
                    </div>
                </div>

                %s
            </article>',
            $titleEsc,
            $chipClassesEsc,
            $statusLabelEsc,
            $specNameEsc,
            $startsDateEsc,
            $startsTimeEsc,
            $endsTimeEsc,
            $usedEsc,
            $totalEsc,
            $pctEsc,
            $totalEsc,
            $usedEsc,
            $barClassEsc,
            $pctEsc,
            $footerHtml
        );

        return $html;
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
