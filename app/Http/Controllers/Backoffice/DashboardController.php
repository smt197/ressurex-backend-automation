<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class DashboardController extends Controller
{
    /**
     * Display the backoffice dashboard.
     */
    public function index(): View
    {
        $totalUsers = User::count();
        $activeUsers = User::whereDate('created_at', today())->count();
        $totalRoles = Role::count();
        $recentUsers = User::latest()->take(5)->get();

        return view('dashboard', compact(
            'totalUsers',
            'activeUsers',
            'totalRoles',
            'recentUsers'
        ));
    }
}
