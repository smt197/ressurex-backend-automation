<?php

namespace App\Http\Controllers;

use App\Models\JobStatus;
use Illuminate\Http\Request;

class JobStatusController extends Controller
{
    public function indexForUser(Request $request)
    {
        $user = $request->user();

        $jobs = JobStatus::where('user_id', $user->id)
            ->whereIn('status', ['queued', 'executing'])
            ->latest()
            ->get();

        return response()->json($jobs);
    }
}
