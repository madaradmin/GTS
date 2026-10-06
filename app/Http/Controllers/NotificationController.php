<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Devuelve las notificaciones del usuario autenticado en formato JSON.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->take(20)
            ->get()
            ->map(function ($notif) {
                return [
                    'id' => $notif->id,
                    'type' => $notif->data['type'] ?? 'info',
                    'title' => $notif->data['title'] ?? 'Notificación GTS',
                    'message' => $notif->data['message'] ?? '',
                    'action_url' => $notif->data['action_url'] ?? '#',
                    'folio' => $notif->data['folio'] ?? null,
                    'icon' => $notif->data['icon'] ?? 'notifications',
                    'level' => $notif->data['level'] ?? 'info',
                    'read' => ! is_null($notif->read_at),
                    'created_at' => $notif->created_at->diffForHumans(),
                    'created_at_exact' => $notif->created_at->format('d M H:i'),
                ];
            });

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    /**
     * Marca una notificación específica como leída.
     */
    public function markAsRead(Request $request, string $id): JsonResponse|RedirectResponse
    {
        $notification = $request->user()
            ->notifications()
            ->where('id', $id)
            ->first();

        if ($notification) {
            $notification->markAsRead();
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        return back()->with('status', 'Notificación marcada como leída.');
    }

    /**
     * Marca todas las notificaciones del usuario autenticado como leídas.
     */
    public function markAllAsRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'unread_count' => 0,
            ]);
        }

        return back()->with('status', 'Todas las notificaciones marcadas como leídas.');
    }
}
