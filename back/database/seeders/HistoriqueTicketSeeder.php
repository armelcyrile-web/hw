<?php

namespace Database\Seeders;

use App\Enums\TypeActionHistorique;
use App\Models\HistoriqueTicket;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;

class HistoriqueTicketSeeder extends Seeder
{
    public function run(): void
    {
        // Récupérer les tickets par leurs titres uniques
        $t1 = Ticket::where('titre', 'Page d\'accueil lente')->first();
        $t2 = Ticket::where('titre', 'Site inaccessible : Blog Pierre')->first();
        $t3 = Ticket::where('titre', 'Problème de certificat SSL')->first();
        $t4 = Ticket::where('titre', 'Site inaccessible : E-commerce Julie')->first();

        $tech1 = User::where('email', 'tech1@hostwatch.local')->first();
        $tech2 = User::where('email', 'tech2@hostwatch.local')->first();
        $admin = User::where('email', 'admin@hostwatch.local')->first();

        // Historique ticket 1 (manuel résolu)
        HistoriqueTicket::create([
            'ticket_id'  => $t1->id,
            'user_id'    => $tech1->id,
            'type_action'=> TypeActionHistorique::PRISE_EN_CHARGE,
            'created_at' => $t1->created_at->addHours(2),
        ]);
        HistoriqueTicket::create([
            'ticket_id'           => $t1->id,
            'user_id'             => $tech1->id,
            'type_action'         => TypeActionHistorique::RESOLUTION,
            'duree_intervention'  => 120, // 2 heures
            'commentaire'         => 'Optimisation des images et mise en cache.',
            'created_at'          => $t1->date_resolution,
        ]);

        // Ticket 3 (assigné, en cours) : prise en charge
        HistoriqueTicket::create([
            'ticket_id'  => $t3->id,
            'user_id'    => $tech2->id,
            'type_action'=> TypeActionHistorique::PRISE_EN_CHARGE,
            'created_at' => $t3->created_at->addMinutes(15),
        ]);

        // Ticket 4 (automatique résolu)
        HistoriqueTicket::create([
            'ticket_id'  => $t4->id,
            'user_id'    => $tech1->id,
            'type_action'=> TypeActionHistorique::PRISE_EN_CHARGE,
            'created_at' => $t4->created_at->addHours(3),
        ]);
        HistoriqueTicket::create([
            'ticket_id'           => $t4->id,
            'user_id'             => $tech1->id,
            'type_action'         => TypeActionHistorique::RESOLUTION,
            'duree_intervention'  => 45, // 45 minutes
            'commentaire'         => 'Redémarrage du serveur et correction DNS.',
            'created_at'          => $t4->date_resolution,
        ]);
    }
}
