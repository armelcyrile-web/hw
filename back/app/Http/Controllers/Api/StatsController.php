<?php

// app/Http/Controllers/Api/StatsController.php

namespace App\Http\Controllers\Api;

use App\Enums\StatutDisponibilite;
use App\Enums\StatutTicket;
use App\Enums\TypeActionHistorique;
use App\Http\Controllers\Controller;
use App\Models\HistoriqueTicket;
use App\Models\Site;
use App\Models\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $stats = Cache::remember('stats-dashboard', 60, function (): array {
            $now = Carbon::now();

            // 1. Cartes de synthèse
            $totalTickets = Ticket::count();

            $ticketsResolusCeMois = Ticket::where('statut', StatutTicket::RESOLU)
                ->whereMonth('date_resolution', $now->month)
                ->whereYear('date_resolution', $now->year)
                ->count();

            $tempsMoyenResolution = (int) round(
                HistoriqueTicket::where('type_action', TypeActionHistorique::RESOLUTION)
                    ->avg('duree_intervention') ?? 0
            );

            $totalSites = Site::count();
            $sitesEnLigne = Site::where('statut_disponibilite', StatutDisponibilite::EN_LIGNE)->count();
            $tauxDisponibilite = $totalSites > 0
                ? round(($sitesEnLigne / $totalSites) * 100, 1)
                : 0.0;

            // 2. Évolution des tickets sur 30 jours
            $debutPeriode = $now->copy()->subDays(29)->startOfDay();
            $ticketsParJour = Ticket::where('created_at', '>=', $debutPeriode)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as total')
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('total', 'date');

            $evolution = [];
            for ($i = 0; $i < 30; $i++) {
                $date = $debutPeriode->copy()->addDays($i)->format('Y-m-d');
                $evolution[] = [
                    'date'  => $date,
                    'total' => $ticketsParJour[$date] ?? 0,
                ];
            }

            // 3. Charge par technicien
            $techniciens = User::where('role', 'technicien')->get();
            $techniciensIds = $techniciens->pluck('id')->toArray();

            // Tickets assignés actuellement par technicien
            $assignes = Ticket::where('statut', StatutTicket::ASSIGNE)
                ->whereIn('technicien_id', $techniciensIds)
                ->selectRaw('technicien_id, COUNT(*) as nb')
                ->groupBy('technicien_id')
                ->pluck('nb', 'technicien_id');

            // Tickets résolus par technicien (via historique)
            $resolus = HistoriqueTicket::where('type_action', TypeActionHistorique::RESOLUTION)
                ->whereIn('user_id', $techniciensIds)
                ->selectRaw('user_id, COUNT(*) as nb')
                ->groupBy('user_id')
                ->pluck('nb', 'user_id');

            $charge = $techniciens->map(function ($tech) use ($assignes, $resolus): array {
                return [
                    'nom'                  => $tech->nom,
                    'prenom'               => $tech->prenom,
                    'tickets_assignes'     => $assignes[$tech->id] ?? 0,
                    'tickets_resolus'      => $resolus[$tech->id] ?? 0,
                ];
            });

            // 4. Répartition par priorité
            $repartitionPriorite = Ticket::selectRaw('priorite, COUNT(*) as count')
                ->groupBy('priorite')
                ->pluck('count', 'priorite')
                ->toArray();

            // 5. Répartition par statut
            $repartitionStatut = Ticket::selectRaw('statut, COUNT(*) as count')
                ->groupBy('statut')
                ->pluck('count', 'statut')
                ->toArray();

            return [
                'resume' => [
                    'total_tickets'                 => $totalTickets,
                    'tickets_resolus_ce_mois'       => $ticketsResolusCeMois,
                    'temps_moyen_resolution_minutes'=> $tempsMoyenResolution,
                    'taux_disponibilite_global'     => $tauxDisponibilite,
                ],
                'evolution_tickets'     => $evolution,
                'charge_par_technicien' => $charge->values()->toArray(),
                'repartition_par_priorite' => $repartitionPriorite,
                'repartition_par_statut'   => $repartitionStatut,
            ];
        });

        return response()->json($stats);
    }
}
