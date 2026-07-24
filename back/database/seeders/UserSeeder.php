<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Administrateur
        User::create([
            'nom'       => 'Dupont',
            'prenom'    => 'Marie',
            'email'     => 'admin@hostwatch.local',
            'password'  => Hash::make('password'),
            'role'      => 'administrateur',
        ]);

        // Techniciens
        User::create([
            'nom'        => 'Martin',
            'prenom'     => 'Luc',
            'email'      => 'tech1@hostwatch.local',
            'password'   => Hash::make('password'),
            'role'       => 'technicien',
            'specialite' => 'Réseau',
        ]);

        User::create([
            'nom'        => 'Petit',
            'prenom'     => 'Sophie',
            'email'      => 'tech2@hostwatch.local',
            'password'   => Hash::make('password'),
            'role'       => 'technicien',
            'specialite' => 'Sécurité',
        ]);

        // Clients
        User::create([
            'nom'       => 'Durand',
            'prenom'    => 'Pierre',
            'email'     => 'client1@hostwatch.local',
            'password'  => Hash::make('password'),
            'role'      => 'client',
            'telephone' => '0601020304',
        ]);

        User::create([
            'nom'       => 'Lefebvre',
            'prenom'    => 'Julie',
            'email'     => 'client2@hostwatch.local',
            'password'  => Hash::make('password'),
            'role'      => 'client',
            'telephone' => '0605060708',
        ]);

        // Un troisième client sans site (pour test interface vide)
        User::create([
            'nom'       => 'Moreau',
            'prenom'    => 'Antoine',
            'email'     => 'client3@hostwatch.local',
            'password'  => Hash::make('password'),
            'role'      => 'client',
            'telephone' => '0608091011',
        ]);
    }
}
