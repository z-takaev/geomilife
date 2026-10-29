<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Password;

final class ForgotPasswordController extends Controller
{
    public function sendResetLink(ForgotPasswordRequest $request): RedirectResponse
    {
        Password::sendResetLink([
            'email' => $request->string('email')->toString(),
        ]);

        return back()
            ->with('auth_modal', 'forgotPassword')
            ->with('auth_status', __('Если аккаунт с таким адресом существует, ссылка для сброса пароля отправлена.'));
    }
}
