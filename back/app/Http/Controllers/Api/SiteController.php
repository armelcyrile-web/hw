<?php

// app/Http/Controllers/Api/SiteController.php

namespace App\Http\Controllers\Api;

use App\Enums\StatutDisponibilite;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSiteRequest;
use App\Http\Requests\UpdateSiteRequest;
use App\Http\Resources\SiteResource;
use App\Models\Site;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class SiteController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();
        if ($user->isClient()) {
            $sites = Site::where('client_id', $user->id)->with('client')->get();
        } else {
            $sites = Site::with('client')->get();
        }

        return SiteResource::collection($sites);
    }

    public function store(StoreSiteRequest $request): JsonResponse
    {
        $this->authorize('create', Site::class);

        // Vérifier que le client_id correspond bien à un utilisateur de rôle client
        $client = User::findOrFail($request->client_id);
        if (!$client->isClient()) {
            return response()->json(['message' => 'L\'utilisateur spécifié n\'est pas un client.'], 422);
        }

        $data = $request->validated();
        // Ajout explicite de la valeur par défaut
        $data['statut_disponibilite'] = StatutDisponibilite::INCONNU;

        $site = Site::create($data);

        return (new SiteResource($site->load('client')))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Site $site): SiteResource
    {
        $this->authorize('view', $site);

        return new SiteResource($site->load('client'));
    }

    public function update(UpdateSiteRequest $request, Site $site): SiteResource
    {
        $this->authorize('update', $site);

        $site->update($request->validated());

        return new SiteResource($site->fresh()->load('client'));
    }

    public function destroy(Site $site): Response
    {
        $this->authorize('delete', $site);

        $site->delete();

        return response()->noContent();
    }
}
