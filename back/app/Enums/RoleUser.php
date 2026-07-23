<?php

namespace App\Enums;

enum RoleUser: string
{
    case CLIENT = 'client';
    case TECHNICIEN = 'technicien';
    case ADMINISTRATEUR = 'administrateur';
}
