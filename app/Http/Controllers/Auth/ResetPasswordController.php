<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class ResetPasswordController extends Controller
{
    public function showResetForm(Request $request, string $token): View
    {
        seo()->title(__('Новый пароль'));

        return view('auth.reset-password', [
            'email' => $request->string('email')->toString(),
            'token' => $token,
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): RedirectResponse
    {
        $status = Password::reset(
            $request->safe()->only(['email', 'password', 'password_confirmation', 'token']),
            static function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ])->errorBag('resetPassword');
        }

        return redirect()->route('home')
            ->with('auth_modal', 'login')
            ->with('auth_status', __('Пароль изменён. Теперь вы можете войти.'));
    }
}
