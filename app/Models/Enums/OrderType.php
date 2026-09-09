<?php

namespace App\Models\Enums;

enum OrderType: string
{
    case DineIn = 'dine_in';
    case Takeaway = 'takeaway';
    case Parcel = 'parcel';
    case Delivery = 'delivery';
}
