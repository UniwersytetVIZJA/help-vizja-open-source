<?php

declare(strict_types=1);

namespace App\Enum\Application;

/**
 * Class ApplicationTypeEnum
 * @package App\Enum\Application
 */
enum ApplicationTypeEnum: string
{
    case EDUCATIONAL_PROCESS = 'Adaptacja procesu kształcenia';
    case LANGUAGE_INTERPRETER = 'Przyznanie tłumacza języka migowego';
    case SPECIALISED_EQUIPMENT = 'Wypożyczenie sprzętu specjalistycznego';
    case TEACHING_ASSISTANT = 'Organizacja wsparcia asystenta dydaktycznego';
}
