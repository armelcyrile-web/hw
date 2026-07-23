<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return true; // Tout utilisateur connecté peut voir la liste (filtrée dans le contrôleur)
    }

    public function view(User $user, Site $site): bool
    {
        if ($user->isAdministrateur() || $user->isTechnicien()) {
            return true;
        }
        return $site->client_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdministrateur();
    }

    public function update(User $user, Site $site): bool
    {
        return $user->isAdministrateur();
    }

    public function delete(User $user, Site $site): bool
    {
        return $user->isAdministrateur();
    }
}
