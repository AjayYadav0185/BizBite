<?php

namespace App\Models\Enums;

enum FoodType: string
{
    case Veg = 'veg';
    case NonVeg = 'non_veg';
    case Egg = 'egg';
}
