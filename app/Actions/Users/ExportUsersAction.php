<?php

declare(strict_types=1);

namespace App\Actions\Users;

use Illuminate\Database\Eloquent\Builder;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ExportUsersAction
{
    public function handle(Builder $query): StreamedResponse
    {
        return response()->streamDownload(static function () use ($query): void {
            $spreadsheet = new Spreadsheet;
            try {
                $sheet = $spreadsheet->getActiveSheet()->setTitle('Пользователи');
                $sheet->fromArray([
                    'ID', 'Имя', 'Фамилия', 'Дата рождения', 'Email', 'Телефон', 'Город',
                    'Заблокирован', 'Email подтверждён', 'Telegram',
                    'Уведомления в Telegram', 'Дата регистрации',
                ]);
                $line = 2;
                foreach ((clone $query)->with('city')->lazy(500) as $user) {
                    $values = [
                        $user->id, $user->first_name, $user->last_name, $user->birth_date?->format('d.m.Y'),
                        $user->email, $user->phone, $user->city?->name, $user->is_blocked ? 'Да' : 'Нет',
                        $user->email_verified_at ? 'Да' : 'Нет', $user->telegram_username,
                        $user->telegram_notifications_enabled ? 'Да' : 'Нет',
                        $user->created_at?->format('d.m.Y H:i:s'),
                    ];
                    foreach ($values as $index => $value) {
                        $sheet->setCellValueExplicit(
                            [$index + 1, $line],
                            $value ?? '',
                            is_int($value) ? DataType::TYPE_NUMERIC : DataType::TYPE_STRING,
                        );
                    }
                    $line++;
                }
                $lastRow = max(1, $line - 1);
                $sheet->freezePane('A2');
                $sheet->getStyle('A1:L1')->getFont()->setBold(true);
                $sheet->getStyle('A1:L'.$lastRow)->getAlignment()->setWrapText(true)->setVertical('top');
                foreach (range('A', 'L') as $column) {
                    $sheet->getColumnDimension($column)->setWidth($column === 'E' ? 45 : 20);
                }
                (new Xlsx($spreadsheet))->save('php://output');
            } finally {
                $spreadsheet->disconnectWorksheets();
            }
        }, 'users-'.now()->format('Y-m-d-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
