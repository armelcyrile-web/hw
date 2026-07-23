<?php

namespace App\Enums;

enum PrioriteTicket: string
{
    case BASSE = 'basse';
    case NORMALE = 'normale';
    case URGENTE = 'urgente';
}
