<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminPasswordController extends Controller
{
    /**
     * Display the admin password form.
     */
    public function show(): View
    {
        return view('auth.admin-password');
    }

    /**
     * Verify the admin password.
     */
    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'admin_password' => ['required', 'string'],
        ]);

        $adminPassword = config('app.admin_password', 'admin123');

        if ($request->input('admin_password') === $adminPassword) {
            session(['admin_password_verified' => true]);

            $intendedUrl = session()->pull('admin_intended_url', '/dashboard');

            return redirect($intendedUrl)->with('success', 'Admin access granted successfully!');
        }

        return back()->withErrors(['admin_password' => 'Invalid admin password. Please try again.']);
    }
}
