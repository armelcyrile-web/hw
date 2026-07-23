<?php

// app/Http/Resources/SiteResource.php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SiteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                         => $this->id,
            'nom'                        => $this->nom,
            'url'                        => $this->url,
            'statut_disponibilite'       => $this->statut_disponibilite?->value ?? 'inconnu',
            'date_derniere_verification' => $this->date_derniere_verification,
            'client'                     => new UserResource($this->whenLoaded('client')),
        ];
    }
}
