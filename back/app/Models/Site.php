<?php

namespace App\Models;

use App\Enums\StatutDisponibilite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Site extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'url',
        'statut_disponibilite',
        'date_derniere_verification',
        'client_id',
    ];

    protected function casts(): array
    {
        return [
            'statut_disponibilite' => StatutDisponibilite::class,
            'date_derniere_verification' => 'datetime',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
}
