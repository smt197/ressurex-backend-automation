<?php

namespace App\Http\Controllers;

use App\Http\Requests\ToggleMaintenanceRequest;
use App\Models\MaintenanceMode;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class MaintenanceController extends Controller
{
    public function status(): JsonResponse
    {
        $maintenance = MaintenanceMode::latest()->first();

        return response()->json([
            'is_active' => $maintenance?->is_active ?? false,
            'message' => $maintenance?->message,
        ]);
    }

    public function toggle(ToggleMaintenanceRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user || !$user->hasRole('admin')) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 403);
        }

        // Récupère le premier enregistrement ou en crée un nouveau si la table est vide.                
        $maintenance = MaintenanceMode::firstOrNew([]);

        // Remplit le modèle avec les nouvelles données.                                                 
        $maintenance->fill([
            'is_active' => $request->is_active,
            'message' => $request->message,
            'activated_at' => now(),
            'activated_by' => $user->id,
        ]);


        $maintenance->save();

        return response()->json([
            'message' => $request->is_active
                ? __('maintenance.actif')
                : __('maintenance.desactif'),
            'data' => $maintenance,
        ]);
    }
}
