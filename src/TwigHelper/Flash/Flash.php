<?php

namespace App\TwigHelper\Flash;

use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Flash extends AbstractExtension
{
    public function __construct(
        private RequestStack $requestStack
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'flash_messages', $this->flashMessages(...), ['is_safe' => ['html']]
            ),
        ];
    }

    /**
     * Renderuje flashe success i error w stylu Tailwinda.
     *
     * @param string[] $types np. ['success', 'error']
     */
    public function flashMessages(array $types = ['success', 'error']): string
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request || !$request->hasSession()) {
            return '';
        }

        $session = $request->getSession();
        $bag = $session->getFlashBag();

        $html = '';

        foreach ($types as $type) {
            $messages = $bag->get($type); // pobiera i czyści flashe danego typu
            foreach ($messages as $message) {
                $html .= $this->renderFlash($type, (string)$message);
            }
        }

        return $html;
    }

    private function renderFlash(string $type, string $message): string
    {
        $messageEsc = htmlspecialchars($message, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $baseWrapperClasses = 'js-flash flex items-start gap-3 rounded-xl px-4 py-3 text-sm transition-all duration-500 ease-out';

        switch ($type) {
            case 'success':
                $wrapperClasses = $baseWrapperClasses . ' border border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-100';
                $iconWrapperClasses = 'mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 dark:bg-emerald-900/70';
                $iconSvg = '
                <svg class="h-4 w-4 text-emerald-700 dark:text-emerald-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                          d="M5 13l4 4L19 7" />
                </svg>
            ';
                break;

            case 'error':
            case 'danger':
                $wrapperClasses = $baseWrapperClasses . ' border border-red-200 bg-red-50 text-red-900 dark:border-red-800 dark:bg-red-950 dark:text-black';
                $iconWrapperClasses = 'mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-red-100 dark:bg-red-900/70';
                $iconSvg = '
                <svg class="h-4 w-4 text-red-700 dark:text-red-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            ';
                break;

            default:
                $wrapperClasses = $baseWrapperClasses . ' border border-slate-200 bg-slate-50 text-slate-900 dark:border-slate-800 dark:bg-slate-950 dark:text-slate-100';
                $iconWrapperClasses = 'mt-0.5 flex h-6 w-6 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-900/70';
                $iconSvg = '
                <svg class="h-4 w-4 text-slate-700 dark:text-slate-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <path stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
                          d="M12 9v4m0 4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            ';
                break;
        }

        return sprintf(
            '<div class="%s">
            <div class="%s">
                %s
            </div>
            <div class="flex-1">
                <p class="font-medium">%s</p>
            </div>
        </div>',
            $wrapperClasses,
            $iconWrapperClasses,
            $iconSvg,
            $messageEsc
        );
    }

}
