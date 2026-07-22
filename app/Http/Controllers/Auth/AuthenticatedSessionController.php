<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'identity' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ]);

        $key = Str::lower(Str::transliterate($validated['identity'])).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            app(AuditLogger::class)->write('authentication', 'login_rate_limited', ['actor_identity' => $validated['identity'], 'outcome' => 'failure', 'http_status' => 429, 'context' => ['reason' => 'rate_limit']], $request);
            throw ValidationException::withMessages([
                'identity' => 'Too many sign-in attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        $field = filter_var($validated['identity'], FILTER_VALIDATE_EMAIL) ? 'email' : 'staff_number';
        $credentials = [$field => $validated['identity'], 'password' => $validated['password'], 'status' => 'active'];

        if (! Auth::attempt($credentials, (bool) ($validated['remember'] ?? false))) {
            RateLimiter::hit($key, 60);
            app(AuditLogger::class)->write('authentication', 'login_failed', ['actor_identity' => $validated['identity'], 'outcome' => 'failure', 'http_status' => 422, 'context' => ['reason' => 'invalid_credentials']], $request);
            throw ValidationException::withMessages(['identity' => 'The supplied EIMS credentials are incorrect.']);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        app(AuditLogger::class)->write('authentication', 'login_succeeded', ['actor_user_id' => $request->user()->id, 'actor_identity' => $request->user()->email, 'http_status' => 302], $request);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        app(AuditLogger::class)->write('authentication', 'logout', ['actor_user_id' => $request->user()?->id, 'actor_identity' => $request->user()?->email, 'http_status' => 302], $request);
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
