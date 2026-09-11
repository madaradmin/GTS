<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AgentShiftController extends Controller
{
    /**
     * Actualiza el estado de disponibilidad del agente GTS en su turno de trabajo.
     */
    public function update(Request $request): RedirectResponse|JsonResponse
    {
        $currentUser = $request->user();
        abort_if(!$currentUser->is_active, 403, 'Usuario inactivo.');

        $validated = $request->validate([
            'status' => ['required', 'in:ACTIVO,NO DISPONIBLE'],
            'reason' => ['nullable', 'string', 'max:255'],
            'agent_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        // Determinar si es un cambio propio o administrativo
        if (!empty($validated['agent_id']) && (int)$validated['agent_id'] !== $currentUser->id) {
            abort_if(!$currentUser->isAdmin(), 403, 'Solo el Administrador puede modificar la disponibilidad de otros agentes.');
            $targetUser = User::findOrFail($validated['agent_id']);
        } else {
            $targetUser = $currentUser;
        }

        // Actualizar el estado y persistir el registro en la bitácora
        $targetUser->setShiftStatus(
            newStatus: $validated['status'],
            reason: $validated['reason'] ?? null,
            ipAddress: $request->ip()
        );

        $statusLabel = $targetUser->shift_status === 'ACTIVO' ? 'ACTIVO' : 'NO DISPONIBLE';
        $message = "Disponibilidad operativa actualizada a: {$statusLabel}.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'status' => $targetUser->shift_status,
                'is_active' => $targetUser->isShiftActive(),
                'updated_at' => $targetUser->shift_status_updated_at?->format('d/m/Y H:i'),
                'message' => $message,
            ]);
        }

        return redirect()->back()->with('status', $message);
    }
}
