<?php

namespace Database\Seeders;

use App\Enums\OrigineTicket;
use App\Enums\PrioriteTicket;
use App\Enums\StatutTicket;
use App\Models\Site;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer quelques sites et techniciens
        $site1 = Site::where('nom', 'Site vitrine Pierre')->first();
        $site2 = Site::where('nom', 'Blog Pierre')->first();
        $site3 = Site::where('nom', 'E-commerce Julie')->first();

        $tech1 = User::where('email', 'tech1@hostwatch.local')->first();
        $tech2 = User::where('email', 'tech2@hostwatch.local')->first();
        $admin = User::where('email', 'admin@hostwatch.local')->first();

        // Ticket manuel résolu
        $t1 = Ticket::create([
            'titre'          => 'Page d\'accueil lente',
            'description'    => 'Le temps de chargement dépasse 8 secondes sur mobile.',
            'statut'         => StatutTicket::RESOLU,
            'origine'        => OrigineTicket::MANUEL,
            'priorite'       => PrioriteTicket::NORMALE,
            'site_id'        => $site1->id,
            'technicien_id'  => $tech1->id,
            'date_resolution'=> now()->subDays(2),
            'created_at'     => now()->subDays(3),
        ]);

        // Ticket automatique urgent (en attente)
        $t2 = Ticket::create([
            'titre'          => 'Site inaccessible : Blog Pierre',
            'description'    => 'Anomalie détectée automatiquement : Code HTTP 500. Site : https://blog.pierre-durand.fr',
            'statut'         => StatutTicket::NOUVEAU,
            'origine'        => OrigineTicket::AUTOMATIQUE,
            'priorite'       => PrioriteTicket::URGENTE,
            'site_id'        => $site2->id,
            'technicien_id'  => null,
            'created_at'     => now()->subDay(),
        ]);

        // Ticket assigné (en cours)
        $t3 = Ticket::create([
            'titre'          => 'Problème de certificat SSL',
            'description'    => 'Le certificat a expiré hier, les clients voient une erreur de sécurité.',
            'statut'         => StatutTicket::ASSIGNE,
            'origine'        => OrigineTicket::MANUEL,
            'priorite'       => PrioriteTicket::URGENTE,
            'site_id'        => $site3->id,
            'technicien_id'  => $tech2->id,
            'created_at'     => now()->subDays(1)->subHours(5),
        ]);

        // Ticket automatique résolu (ancien)
        $t4 = Ticket::create([
            'titre'          => 'Site inaccessible : E-commerce Julie',
            'description'    => 'Anomalie détectée automatiquement : Timeout. Site : https://www.julie-lefebvre.com',
            'statut'         => StatutTicket::RESOLU,
            'origine'        => OrigineTicket::AUTOMATIQUE,
            'priorite'       => PrioriteTicket::URGENTE,
            'site_id'        => $site3->id,
            'technicien_id'  => $tech1->id,
            'date_resolution'=> now()->subDays(10),
            'created_at'     => now()->subDays(11),
        ]);

        // Un autre ticket manuel basse priorité (nouveau)
        Ticket::create([
            'titre'          => 'Changer le favicon',
            'description'    => 'Le favicon actuel est moche, merci de le remplacer par le nouveau logo.',
            'statut'         => StatutTicket::NOUVEAU,
            'origine'        => OrigineTicket::MANUEL,
            'priorite'       => PrioriteTicket::BASSE,
            'site_id'        => $site1->id,
            'technicien_id'  => null,
            'created_at'     => now()->subDays(5),
        ]);

        // Stocker les tickets dans des variables pour l'historique
        // (on peut les passer via une propriété statique ou les recréer dans HistoriqueTicketSeeder)
        // Pour la simplicité, l'HistoriqueTicketSeeder récupérera les tickets par leur titre.
    }
}
