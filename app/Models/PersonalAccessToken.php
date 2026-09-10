<?php

namespace App\Models;

use Laravel\Sanctum\PersonalAccessToken as SanctumToken;

/**
 * Sanctum token model bound to the `tbl_pos_` prefixed table.
 */
class PersonalAccessToken extends SanctumToken
{
    protected $table = 'tbl_pos_personal_access_tokens';
}
