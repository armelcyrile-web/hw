<?php

namespace App\Enums;

enum StatutTicket: string
{
    case NOUVEAU = 'nouveau';
    case ASSIGNE = 'assigne';
    case RESOLU = 'resolu';
}
