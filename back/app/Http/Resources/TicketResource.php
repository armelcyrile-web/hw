<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'titre'            => $this->titre,
            'description'      => $this->description,
            'statut'           => $this->statut->value,
            'origine'          => $this->origine->value,
            'priorite'         => $this->priorite->value,
            'date_creation'    => $this->created_at,
            'date_resolution'  => $this->date_resolution,
            'site'             => new SiteResource($this->whenLoaded('site')),
            'technicien'       => new UserResource($this->whenLoaded('technicien')),
            'historique'       => HistoriqueTicketResource::collection($this->whenLoaded('historiqueTickets')),
        ];
    }
}
