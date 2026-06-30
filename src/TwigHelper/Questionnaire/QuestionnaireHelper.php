<?php

namespace App\TwigHelper\Questionnaire;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class QuestionnaireHelper extends AbstractExtension
{
    public function __construct(
        private UrlGeneratorInterface $router
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('survey_callout', $this->surveyCallout(...), ['is_safe' => ['html']]),
        ];
    }

    public function surveyCallout(
        string $title,
        string $description,
        string $routeName,
        array $routeParams = [],
        string $buttonLabel = 'Wypełnij ankietę',
        array $options = []
    ): string {
        $defaults = [
            'card_classes' => '',
            'light_image_src' => 'https://flowbite.s3.amazonaws.com/blocks/e-commerce/girl-shopping-list.svg',
            'dark_image_src' => 'https://flowbite.s3.amazonaws.com/blocks/e-commerce/girl-shopping-list-dark.svg',
        ];
        $options = array_merge($defaults, $options);

        $href = $this->router->generate($routeName, $routeParams);

        $titleEsc = htmlspecialchars($title, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $descEsc = htmlspecialchars($description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $buttonLabelEsc = htmlspecialchars($buttonLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $hrefEsc = htmlspecialchars($href, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $lightImgEsc = htmlspecialchars($options['light_image_src'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $darkImgEsc = htmlspecialchars($options['dark_image_src'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        $baseClasses = 'mt-6 rounded-2xl border border-gray-700 bg-gray-900 px-6 py-8 text-center shadow-sm';
        $classes = trim($baseClasses . ' ' . ($options['card_classes'] ?? ''));
        $classesEsc = htmlspecialchars($classes, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return sprintf(
            '<div class="%s">
                <div class="flex flex-col items-center">
                    <img
                        class="mx-auto h-28 dark:hidden"
                        src="%s"
                        alt="survey illustration"
                    >
                    <img
                        class="mx-auto hidden h-28 dark:block"
                        src="%s"
                        alt="survey illustration"
                    >

                    <h3 class="mt-6 text-xl font-semibold leading-none text-gray-900 dark:text-white">
                        %s
                    </h3>

                    <p class="mt-3 max-w-2xl text-sm text-gray-600 dark:text-gray-300">
                        %s
                    </p>
                </div>

                <div class="mt-6">
                    <a href="%s">
                        <button
                            type="button"
                            class="inline-flex w-full items-center justify-center rounded-xl border border-blue-600 bg-blue-600 px-6 py-3 text-sm font-semibold text-white
                                   hover:border-blue-700 hover:bg-blue-700  focus:ring-4 focus:ring-blue-300
                                   dark:border-blue-600 dark:bg-blue-600 dark:hover:border-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800"
                        >
                            %s
                        </button>
                    </a>
                </div>
            </div>',
            $classesEsc,
            $lightImgEsc,
            $darkImgEsc,
            $titleEsc,
            $descEsc,
            $hrefEsc,
            $buttonLabelEsc
        );
    }
}
