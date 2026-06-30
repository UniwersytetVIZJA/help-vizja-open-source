<?php

namespace App\Enum\Application;

enum ApplicationDiscrEnum: string
{
    case EDUCATIONAL_PROCESS = 'educational-process';
    case LANGUAGE_INTERPRETER = 'language-interpreter';
    case SPECIALISED_EQUIPMENT = 'specialised-equipment';
    case TEACHING_ASSISTANT = 'teaching-assistant';
}
