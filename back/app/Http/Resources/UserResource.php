<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'nom'        => $this->nom,
            'prenom'     => $this->prenom,
            'email'      => $this->email,
            'role'       => $this->role->value,
            'telephone'  => $this->when($this->role->value === 'client', $this->telephone),
            'specialite' => $this->when($this->role->value === 'technicien', $this->specialite),
        ];
    }
}
