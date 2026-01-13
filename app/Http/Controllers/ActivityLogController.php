<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityLogCollection;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityLogController extends Controller
{
    /**
     * Récupère tous les logs d'activité et les retourne en JSON.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Activity::query();

        if ($request->has(['date_debut', 'date_fin'])) {
            $query->whereBetween('created_at', [
                Carbon::parse($request->date_debut),
                Carbon::parse($request->date_fin),
            ]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('event', 'like', "%{$search}%")
                    ->orWhere('subject_type', 'like', "%{$search}%")
                    ->orWhere('causer_type', 'like', "%{$search}%");
            });
        }

        $logs = $query->latest()->paginate(10);

        return new ActivityLogCollection($logs);
    }
}
