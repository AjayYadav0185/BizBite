<?php

namespace App\Models\Enums;

enum OrderStatus: string
{
    case Completed = 'completed';
    case Cancelled = 'cancelled';
}