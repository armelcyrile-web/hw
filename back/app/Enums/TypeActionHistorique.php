<?php

namespace App\Enums;

enum TypeActionHistorique: string
{
    case PRISE_EN_CHARGE = 'prise_en_charge';
    case RESOLUTION = 'resolution';
    case LIBERATION = 'liberation';
}
