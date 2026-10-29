<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

final class EnsureUserIsNotBlocked
{
    public function handle(Request $request, Closure $next): Response|RedirectResponse
    {
        if ($request->user()?->is_blocked) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('home')
                ->withErrors([
                    'email' => __('Доступ к аккаунту ограничен.'),
                ], 'signIn');
        }

        return $next($request);
    }
}
