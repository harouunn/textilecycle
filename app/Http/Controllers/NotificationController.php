<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Afficher les notifications de l'utilisateur connecté
     */
    public function index(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'titre' => $notification->data['titre'] ?? '',
                    'message' => $notification->data['message'] ?? '',
                    'lien' => $notification->data['lien'] ?? '#',
                    'lu' => $notification->read_at !== null,
                    'priorite_haute' => $notification->data['priorite_haute'] ?? false,
                    'created_at' => $notification->created_at->diffForHumans(),
                ];
            });

        $nonLues = $request->user()->unreadNotifications()->count();

        return response()->json([
            'notifications' => $notifications,
            'non_lues' => $nonLues,
        ]);
    }

    /**
     * Marquer une notification comme lue
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        return response()->json([
            'success' => true,
            'non_lues' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Marquer toutes les notifications comme lues
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json([
            'success' => true,
            'non_lues' => 0,
        ]);
    }

    /**
     * Obtenir le nombre de notifications non lues
     */
    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $request->user()->unreadNotifications()->count(),
        ]);
    }
}
