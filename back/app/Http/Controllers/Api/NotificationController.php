<?php

// app/Http/Controllers/Api/NotificationController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Liste les 20 dernières notifications de l'utilisateur connecté.
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()->notifications()
            ->latest()
            ->take(20)
            ->get()
            ->map(function ($notification) {
                return [
                    'id'   => $notification->id,
                    'type' => class_basename($notification->type),
                    'data' => $notification->data,
                    'lu'   => !is_null($notification->read_at),
                    'date' => $notification->created_at->toIso8601String(),
                ];
            });

        return response()->json(['data' => $notifications]);
    }

    /**
     * Nombre de notifications non lues.
     */
    public function countNonLues(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Marque une notification comme lue.
     */
    public function marquerCommeLue(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'Notification marquée comme lue.']);
    }

    /**
     * Marque toutes les notifications comme lues.
     */
    public function marquerToutesCommeLues(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'Toutes les notifications ont été marquées comme lues.']);
    }
}
