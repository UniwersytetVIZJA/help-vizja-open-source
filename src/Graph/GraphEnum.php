<?php

namespace App\Graph;

enum GraphEnum: string
{
    case ACCESS_TOKEN = 'ms_access_token';
    case ACCESS_TOKEN_EXPIRES = 'ms_access_token_expires_at';
    case REFRESH_TOKEN = 'ms_refresh_token';

    case CLIENT_ADMIN = 'azure_admin';
    case CLIENT_STUDENT = 'azure_student';
}
