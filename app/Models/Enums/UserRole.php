<?php

namespace App\Models\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Cashier = 'cashier';
}