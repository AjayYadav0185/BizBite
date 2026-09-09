<?php

namespace App\Models\Enums;

enum PaymentMode: string
{
    case Cash = 'cash';
    case Upi = 'upi';
    case Card = 'card';
    case Credit = 'credit';
    case Split = 'split';
}