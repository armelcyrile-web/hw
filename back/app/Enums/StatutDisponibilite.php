<?php

namespace App\Enums;

enum StatutDisponibilite: string
{
    case EN_LIGNE = 'en_ligne';
    case HORS_LIGNE = 'hors_ligne';
    case INCONNU = 'inconnu';
}
