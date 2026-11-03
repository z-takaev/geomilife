<?php

declare(strict_types=1);

namespace App\Actions\Users;

use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportUsersAction
{
    public function handle(Builder $query): StreamedResponse
    {
        return response()->streamDownload(function () use ($query): void {
            $stream = fopen('php://output', 'wb');

            try {
                fwrite($stream, "\xEF\xBB\xBF");
                fputcsv($stream, [
                    'ID', 'Имя', 'Фамилия', 'Дата рождения', 'Email', 'Телефон', 'Город',
                    'Заблокирован', 'Email подтверждён', 'Телефон подтверждён',
                    'Telegram', 'Уведомления в Telegram', 'Дата регистрации',
                ], ';', '"', '');

                foreach ((clone $query)->with('city')->lazy(500) as $user) {
                    $values = [
                        $user->id,
                        $user->first_name,
                        $user->last_name,
                        $user->birth_date?->format('Y-m-d'),
                        $user->email,
                        $user->phone,
                        $user->city?->name,
                        $user->is_blocked ? 'Да' : 'Нет',
                        $user->email_verified_at ? 'Да' : 'Нет',
                        $user->phone_verified_at ? 'Да' : 'Нет',
                        $user->telegram_username,
                        $user->telegram_notifications_enabled ? 'Да' : 'Нет',
                        $user->created_at?->format('Y-m-d H:i:s'),
                    ];

                    fputcsv($stream, array_map($this->sanitizeCell(...), $values), ';', '"', '');
                }
            } finally {
                fclose($stream);
            }
        }, 'users-'.now()->format('Y-m-d-His').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function sanitizeCell(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[\s\x{FEFF}]*[=+\-@\t\r\n]/u', $value) ? "'".$value : $value;
    }
}
