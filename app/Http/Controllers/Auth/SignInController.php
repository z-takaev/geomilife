<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SignInRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

final class SignInController extends Controller
{
    public function login(SignInRequest $request): RedirectResponse
    {
        $authenticated = Auth::attempt(
            credentials: [
                'email' => $request->string('email')->toString(),
                'password' => $request->string('password')->toString(),
                'is_blocked' => false,
            ],
            remember: $request->boolean('remember'),
        );

        if (! $authenticated) {
            throw ValidationException::withMessages([
                'email' => [__('Не удалось войти с указанными данными.')],
            ])->errorBag('signIn');
        }

        $request->session()->regenerate();
        $request->session()->forget('favorites.merge_declined');

        return redirect()->intended(route('profile.show'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
