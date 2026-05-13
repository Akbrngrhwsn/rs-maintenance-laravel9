<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Redirect berdasarkan role
        $user = Auth::user();
        // If the intended URL is a notification-check (likely an AJAX poll),
        // ignore it so the user isn't redirected to a JSON endpoint after login.
        $intended = $request->session()->get('url.intended', '');
        $ignorePaths = [
            '/notifications/check',
        ];

        $shouldIgnoreIntended = false;
        foreach ($ignorePaths as $p) {
            if ($p !== '' && str_contains($intended, $p)) {
                $shouldIgnoreIntended = true;
                break;
            }
        }

        // Admin IT ke dashboard, selain itu ke form lapor kerusakan
        if ($user->role === 'admin') {
            if ($shouldIgnoreIntended) {
                return redirect()->route('dashboard');
            }
            return redirect()->intended(route('dashboard', absolute: false));
        } else {
            // Manager, Direktur, Bendahara, Kepala Ruang, dan non-admin lainnya ke form lapor
            if ($shouldIgnoreIntended) {
                return redirect()->route('public.home');
            }
            return redirect()->intended(route('public.home', absolute: false));
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
