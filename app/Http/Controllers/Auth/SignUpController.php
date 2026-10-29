<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SignUpRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class SignUpController extends Controller
{
    public function register(SignUpRequest $request): RedirectResponse
    {
        $data = $request->safe()->only([
            'first_name',
            'last_name',
            'email',
            'password',
        ]);

        $user = User::query()->create($data);

        Auth::login($user);
        $request->session()->regenerate();

        event(new Registered($user));

        return redirect()->route('verification.notice');
    }
}
