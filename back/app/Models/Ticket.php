<?php

namespace App\Models;

use App\Enums\OrigineTicket;
use App\Enums\PrioriteTicket;
use App\Enums\StatutTicket;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'titre',
        'description',
        'statut',
        'origine',
        'priorite',
        'site_id',
        'technicien_id',
        'date_resolution',
    ];

    protected function casts(): array
    {
        return [
            'statut' => StatutTicket::class,
            'origine' => OrigineTicket::class,
            'priorite' => PrioriteTicket::class,
            'date_resolution' => 'datetime',
        ];
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    public function technicien(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technicien_id');
    }

    public function historiqueTickets(): HasMany
    {
        return $this->hasMany(HistoriqueTicket::class);
    }

    public function scopeNouveaux(Builder $query): Builder
    {
        return $query->where('statut', StatutTicket::NOUVEAU);
    }

    public function scopeUrgents(Builder $query): Builder
    {
        return $query->where('priorite', PrioriteTicket::URGENTE);
    }

    public function scopeParTechnicien(Builder $query, int $technicienId): Builder
    {
        return $query->where('technicien_id', $technicienId);
    }

    public function scopeNonResolus(Builder $query): Builder
    {
        return $query->whereNot('statut', StatutTicket::RESOLU);
    }
}
