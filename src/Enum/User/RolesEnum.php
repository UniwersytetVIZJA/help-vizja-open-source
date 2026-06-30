<?php

namespace App\Enum\User;

enum RolesEnum: string
{
    case ADMIN = 'ROLE_ADMIN';
    case PRACOWNIK = 'ROLE_EMPLOYEE';
    case SPECJALISTA = 'ROLE_SPECIALIST';
}
