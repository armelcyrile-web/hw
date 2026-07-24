<?php

namespace Database\Seeders;

use App\Models\Site;
use Illuminate\Database\Seeder;

class SiteSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les clients (emails fixes)
        $client1 = \App\Models\User::where('email', 'client1@hostwatch.local')->first();
        $client2 = \App\Models\User::where('email', 'client2@hostwatch.local')->first();

        // Sites pour client1 (Pierre Durand)
        Site::create([
            'nom'                        => 'Site vitrine Pierre',
            'url'                        => 'https://www.pierre-durand.fr',
            'statut_disponibilite'       => 'en_ligne',
            'date_derniere_verification' => now()->subMinutes(10),
            'client_id'                  => $client1->id,
        ]);

        Site::create([
            'nom'                        => 'Blog Pierre',
            'url'                        => 'https://blog.pierre-durand.fr',
            'statut_disponibilite'       => 'hors_ligne',
            'date_derniere_verification' => now()->subMinutes(5),
            'client_id'                  => $client1->id,
        ]);

        // Sites pour client2 (Julie Lefebvre)
        Site::create([
            'nom'                        => 'E-commerce Julie',
            'url'                        => 'https://www.julie-lefebvre.com',
            'statut_disponibilite'       => 'en_ligne',
            'date_derniere_verification' => now()->subHour(),
            'client_id'                  => $client2->id,
        ]);

        // Un site avec statut inconnu (pour varier)
        Site::create([
            'nom'                        => 'Ancien portfolio',
            'url'                        => 'https://portfolio.julie-lefebvre.com',
            'statut_disponibilite'       => 'inconnu',
            'date_derniere_verification' => null,
            'client_id'                  => $client2->id,
        ]);
    }
}
