<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        $settingValue = Setting::get('enable_demo_login');
        $demoLoginEnabled = $settingValue !== null
            ? filter_var($settingValue, FILTER_VALIDATE_BOOLEAN)
            : (bool) config('auth.demo_login_enabled', true);

        return Inertia::render('Auth/Login', [
            'status' => session('status'),
            'demoLoginEnabled' => $demoLoginEnabled,
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Welcome back, '.Auth::user()->name.'!');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('info', 'You have been logged out successfully.');
    }
}
