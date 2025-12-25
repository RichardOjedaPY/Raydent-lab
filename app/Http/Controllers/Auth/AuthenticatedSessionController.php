<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Support\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        // 🧾 AUDIT: vio pantalla de login (opcional)
        Audit::log('auth', 'login_view', 'Vio pantalla de inicio de sesión', null, [
            'ip'         => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        try {
            $request->authenticate();

            $request->session()->regenerate();

            // ✅ Ya autenticado
            $user = $request->user();

            // 🧾 AUDIT: login exitoso
            Audit::log('auth', 'login_success', 'Inicio de sesión exitoso', $user, [
                'user_id'    => (int) ($user->id ?? 0),
                'email'      => (string) ($user->email ?? ''),
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->route('dashboard');

        } catch (ValidationException $e) {
            // 🧾 AUDIT: login fallido (sin exponer contraseña)
            Audit::log('auth', 'login_failed', 'Intento de inicio de sesión fallido', null, [
                'email'      => (string) $request->input('email', ''),
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw $e; // importante: no romper el flujo de Laravel/Breeze
        }
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Capturamos usuario ANTES del logout
        $user = $request->user();

        // 🧾 AUDIT: logout
        Audit::log('auth', 'logout', 'Cierre de sesión', $user, [
            'user_id'    => (int) ($user->id ?? 0),
            'email'      => (string) ($user->email ?? ''),
            'ip'         => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
