<?php

// app/Http/Controllers/Api/TicketController.php

namespace App\Http\Controllers\Api;

use App\Enums\OrigineTicket;
use App\Enums\StatutTicket;
use App\Enums\TypeActionHistorique;
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignerTicketRequest;
use App\Http\Requests\ResoudreTicketRequest;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Resources\TicketResource;
use App\Models\HistoriqueTicket;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NouveauTicketNotification;
use App\Notifications\TicketLibereNotification;
use App\Notifications\TicketResoluNotification;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Notification;

class TicketController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        $query = Ticket::with(['site.client', 'technicien']);

        // Filtrage par rôle
        if ($user->isClient()) {
            $query->whereHas('site', fn ($q) => $q->where('client_id', $user->id));
        } elseif ($user->isTechnicien()) {
            // Technicien : tous les tickets, filtres optionnels
            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }
            if ($request->filled('priorite')) {
                $query->where('priorite', $request->priorite);
            }
        } elseif ($user->isAdministrateur()) {
            // Admin : tous les tickets, filtres optionnels
            if ($request->filled('statut')) {
                $query->where('statut', $request->statut);
            }
            if ($request->filled('priorite')) {
                $query->where('priorite', $request->priorite);
            }
        }

        return TicketResource::collection($query->get());
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $this->authorize('create', Ticket::class);

        $validated = $request->validated();
        $site = \App\Models\Site::findOrFail($validated['site_id']);

        // Vérifie que le site appartient bien au client connecté
        if ($site->client_id !== $request->user()->id) {
            return response()->json(['message' => 'Ce site ne vous appartient pas.'], 403);
        }

        $ticket = Ticket::create([
            'titre'       => $validated['titre'],
            'description' => $validated['description'],
            'priorite'    => $validated['priorite'],
            'site_id'     => $validated['site_id'],
            'statut'      => StatutTicket::NOUVEAU,
            'origine'     => OrigineTicket::MANUEL,
        ]);

        // Notification au client propriétaire du site (confirmation in-app)
        if ($ticket->site->client) {
            $ticket->site->client->notify(new NouveauTicketNotification($ticket));
        }

        // Notification à tous les techniciens et administrateurs
        $staff = User::whereIn('role', ['technicien', 'administrateur'])->get();
        Notification::send($staff, new NouveauTicketNotification($ticket));

        return (new TicketResource($ticket->load(['site.client', 'technicien'])))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Ticket $ticket): TicketResource
    {
        $this->authorize('view', $ticket);

        return new TicketResource($ticket->load(['site.client', 'technicien', 'historiqueTickets.user']));
    }

    public function prendreEnCharge(Ticket $ticket): JsonResponse
    {
        $this->authorize('prendreEnCharge', $ticket);

        $user = request()->user();
        $ticket->update([
            'statut'         => StatutTicket::ASSIGNE,
            'technicien_id'  => $user->id,
        ]);

        HistoriqueTicket::create([
            'ticket_id'  => $ticket->id,
            'user_id'    => $user->id,
            'type_action' => TypeActionHistorique::PRISE_EN_CHARGE,
        ]);

        // TODO: Déclencher notification de prise en charge

        return response()->json([
            'message' => 'Ticket pris en charge avec succès.',
            'ticket'  => new TicketResource($ticket->load(['site.client', 'technicien', 'historiqueTickets.user'])),
        ]);
    }

    public function assigner(AssignerTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('assigner', $ticket);

        $technicien = User::findOrFail($request->technicien_id);
        if (!$technicien->isTechnicien()) {
            return response()->json(['message' => 'L\'utilisateur spécifié n\'est pas un technicien.'], 422);
        }

        $ticket->update([
            'statut'         => StatutTicket::ASSIGNE,
            'technicien_id'  => $technicien->id,
        ]);

        HistoriqueTicket::create([
            'ticket_id'   => $ticket->id,
            'user_id'     => request()->user()->id, // l'admin qui a assigné
            'type_action' => TypeActionHistorique::PRISE_EN_CHARGE,
            'commentaire' => 'Assignation manuelle par l\'administrateur au technicien ' . $technicien->nom . ' ' . $technicien->prenom,
        ]);

        // TODO: Déclencher notification d'assignation

        return response()->json([
            'message' => 'Ticket assigné avec succès.',
            'ticket'  => new TicketResource($ticket->load(['site.client', 'technicien', 'historiqueTickets.user'])),
        ]);
    }

    public function resoudre(ResoudreTicketRequest $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('resoudre', $ticket);

        // Vérification supplémentaire : statut assigné
        if ($ticket->statut !== StatutTicket::ASSIGNE) {
            return response()->json(['message' => 'Le ticket n\'est pas au statut assigné.'], 409);
        }

        $now = Carbon::now();
        $ticket->update([
            'statut'          => StatutTicket::RESOLU,
            'date_resolution' => $now,
        ]);

        // Calcul de la durée d'intervention : différence en minutes entre maintenant et la dernière prise_en_charge
        $dernierePriseEnCharge = $ticket->historiqueTickets()
            ->where('type_action', TypeActionHistorique::PRISE_EN_CHARGE)
            ->latest('created_at')
            ->first();

        $duree = null;
        if ($dernierePriseEnCharge) {
            $duree = abs(floor($dernierePriseEnCharge->created_at->diffInMinutes($now)));
        }

        HistoriqueTicket::create([
            'ticket_id'           => $ticket->id,
            'user_id'             => $request->user()->id,
            'type_action'         => TypeActionHistorique::RESOLUTION,
            'duree_intervention'  => $duree,
            'commentaire'         => $request->input('commentaire'),
        ]);

        // Notification au client
        if ($ticket->site->client) {
            $ticket->site->client->notify(new TicketResoluNotification($ticket));
        }

        // Notification à l'équipe (techniciens + admin)
        $staff = User::whereIn('role', ['technicien', 'administrateur'])->get();
        Notification::send($staff, new TicketResoluNotification($ticket));

        return response()->json([
            'message' => 'Ticket résolu avec succès.',
            'ticket'  => new TicketResource($ticket->load(['site.client', 'technicien', 'historiqueTickets.user'])),
        ]);
    }

    public function liberer(Ticket $ticket): JsonResponse
    {
        $this->authorize('liberer', $ticket);

        if ($ticket->statut !== StatutTicket::ASSIGNE) {
            return response()->json(['message' => 'Le ticket n\'est pas au statut assigné.'], 409);
        }

        $ticket->update([
            'statut'         => StatutTicket::NOUVEAU,
            'technicien_id'  => null,
        ]);

        HistoriqueTicket::create([
            'ticket_id'  => $ticket->id,
            'user_id'    => request()->user()->id,
            'type_action' => TypeActionHistorique::LIBERATION,
        ]);

        // Notification uniquement aux techniciens et administrateurs
        $staff = User::whereIn('role', ['technicien', 'administrateur'])->get();
        Notification::send($staff, new TicketLibereNotification($ticket));

        return response()->json([
            'message' => 'Ticket libéré avec succès.',
            'ticket'  => new TicketResource($ticket->load(['site.client', 'technicien', 'historiqueTickets.user'])),
        ]);
    }
}
