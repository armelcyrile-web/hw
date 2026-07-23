<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HistoriqueTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                  => $this->id,
            'type_action'         => $this->type_action->value,
            'duree_intervention'  => $this->duree_intervention,
            'commentaire'         => $this->commentaire,
            'date_action'         => $this->created_at,
            'utilisateur'         => new UserResource($this->whenLoaded('user')),
        ];
    }
}
