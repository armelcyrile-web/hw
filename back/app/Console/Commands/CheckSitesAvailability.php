<?php

namespace App\Console\Commands;

use App\Enums\OrigineTicket;
use App\Enums\PrioriteTicket;
use App\Enums\StatutDisponibilite;
use App\Enums\StatutTicket;
use App\Models\Site;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NouveauTicketNotification;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class CheckSitesAvailability extends Command
{
    protected $signature = 'sites:check-availability';
    protected $description = 'Vérifie la disponibilité de tous les sites surveillés et crée des tickets automatiques en cas de panne';

    public function handle(): int
    {
        $total = 0;
        $enLigne = 0;
        $horsLigne = 0;
        $nouveauxTickets = 0;

        Site::chunk(50, function ($sites) use (&$total, &$enLigne, &$horsLigne, &$nouveauxTickets): void {
            foreach ($sites as $site) {
                $total++;
                $url = $site->url;

                // Préfixe https:// si aucun schéma présent
                if (!preg_match('/^https?:\/\//i', $url)) {
                    $url = 'https://'.$url;
                }

                $start = microtime(true);
                $anomaly = false;
                $anomalyDescription = '';

                try {
                    $response = Http::timeout(5)->get($url);
                    $statusCode = $response->status();

                    if ($statusCode >= 400 && $statusCode < 600) {
                        $anomaly = true;
                        $anomalyDescription = "Code HTTP {$statusCode}";
                    }
                } catch (ConnectionException $e) {
                    $anomaly = true;
                    $anomalyDescription = 'Timeout ou erreur de connexion (DNS, connexion refusée) : '.$e->getMessage();
                } catch (\Exception $e) {
                    $anomaly = true;
                    $anomalyDescription = 'Erreur de connexion : '.$e->getMessage();
                }

                $duration = round(microtime(true) - $start, 2);

                // Mise à jour du statut et de la date de dernière vérification
                $site->update([
                    'statut_disponibilite' => $anomaly ? StatutDisponibilite::HORS_LIGNE : StatutDisponibilite::EN_LIGNE,
                    'date_derniere_verification' => now(),
                ]);

                if ($anomaly) {
                    $horsLigne++;
                    Log::warning("Site hors ligne détecté : {$site->nom} ({$url}) - {$anomalyDescription}");

                    // Vérifier qu'aucun ticket non résolu n'existe déjà pour ce site
                    $ticketExistant = Ticket::where('site_id', $site->id)
                        ->where('statut', '!=', StatutTicket::RESOLU)
                        ->exists();

                    if (!$ticketExistant) {
                        try {
                            $ticketCree = Ticket::create([
                                'titre' => "Site inaccessible : {$site->nom}",
                                'description' => "Anomalie détectée automatiquement : {$anomalyDescription}. Site : {$url}",
                                'statut' => StatutTicket::NOUVEAU,
                                'origine' => OrigineTicket::AUTOMATIQUE,
                                'priorite' => PrioriteTicket::URGENTE,
                                'site_id' => $site->id,
                                'technicien_id' => null,
                            ]);
                            $nouveauxTickets++;
                            $this->warn("Ticket créé pour le site {$site->nom} : {$anomalyDescription}");

                            // Notification aux techniciens et administrateurs (pas au client pour une détection automatique)
                            $staff = User::whereIn('role', ['technicien', 'administrateur'])->get();
                            Notification::send($staff, new NouveauTicketNotification($ticketCree));

                        } catch (\Exception $e) {
                            Log::error("Échec de la création du ticket pour le site {$site->nom} : ".$e->getMessage());
                            $this->error("Erreur création ticket pour {$site->nom}");
                        }
                    } else {
                        $this->line("Ticket non résolu existant pour {$site->nom}, pas de nouveau ticket.");
                    }
                } else {
                    $enLigne++;
                    Log::info("Site en ligne : {$site->nom} ({$url}) - durée {$duration}s");
                    $this->info("OK : {$site->nom}");
                }
            }
        });

        $this->newLine();
        $this->info('=== Résumé de la vérification ===');
        $this->line("Sites vérifiés : {$total}");
        $this->info("En ligne : {$enLigne}");
        $this->error("Hors ligne : {$horsLigne}");
        $this->warn("Nouveaux tickets créés : {$nouveauxTickets}");

        return Command::SUCCESS;
    }
}
