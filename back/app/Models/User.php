<?php

namespace App\Models;

use App\Enums\RoleUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nom',
        'prenom',
        'email',
        'password',
        'role',
        'telephone',
        'specialite',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => RoleUser::class,
        ];
    }

    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'client_id');
    }

    public function ticketsAssignes(): HasMany
    {
        return $this->hasMany(Ticket::class, 'technicien_id');
    }

    public function historiqueTickets(): HasMany
    {
        return $this->hasMany(HistoriqueTicket::class);
    }

    public function isClient(): bool
    {
        return $this->role === RoleUser::CLIENT;
    }

    public function isTechnicien(): bool
    {
        return $this->role === RoleUser::TECHNICIEN;
    }

    public function isAdministrateur(): bool
    {
        return $this->role === RoleUser::ADMINISTRATEUR;
    }

    public function isStaff(): bool
    {
        return $this->isTechnicien() || $this->isAdministrateur();
    }
}
