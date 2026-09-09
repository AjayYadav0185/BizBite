<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

/**
 * Sanctum token model bound to the `tbl_` prefixed table.
 */
class PersonalAccessToken extends SanctumToken
{
    protected $table = 'tbl_personal_access_tokens';
}
