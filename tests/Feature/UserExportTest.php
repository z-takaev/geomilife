<?php

declare(strict_types=1);

use App\Actions\Users\ExportUsersAction;
use App\Models\City;
use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Symfony\Component\HttpFoundation\StreamedResponse;

function userExportSpreadsheet(StreamedResponse $response): Spreadsheet
{
    ob_start();

    try {
        $response->sendContent();
        $contents = (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }

    $path = storage_path('framework/testing/users-'.Str::uuid().'.xlsx');
    if (! is_dir(dirname($path))) {
        mkdir(dirname($path), 0755, true);
    }
    file_put_contents($path, $contents);

    try {
        return IOFactory::load($path);
    } finally {
        unlink($path);
    }
}

it('экспортирует пользователей в минимально оформленный файл с правильными типами', function (): void {
    expect(Schema::hasColumn('users', 'phone_verified_at'))->toBeFalse();

    $city = City::query()->create(['cdek_code' => 44, 'name' => 'Москва', 'is_active' => true]);
    $user = User::query()->create([
        'first_name' => 'Иван',
        'last_name' => 'Иванов',
        'email' => 'ivan@example.test',
        'phone' => '+79991234567',
        'password' => 'password',
        'city_id' => $city->id,
        'telegram_username' => 'ivan',
    ]);

    $spreadsheet = userExportSpreadsheet(app(ExportUsersAction::class)->handle(User::query()->whereKey($user->id)));

    try {
        $sheet = $spreadsheet->getActiveSheet();

        expect($spreadsheet->getSheetCount())->toBe(1)
            ->and($sheet->getTitle())->toBe('Пользователи')
            ->and($sheet->getHighestDataColumn())->toBe('L')
            ->and($sheet->getFreezePane())->toBe('A2')
            ->and($sheet->getAutoFilter()->getRange())->toBe('')
            ->and($sheet->getStyle('A1')->getBorders()->getLeft()->getBorderStyle())->toBe(Border::BORDER_NONE)
            ->and($sheet->getCell('A2')->getValue())->toBe($user->id)
            ->and($sheet->getCell('A2')->getDataType())->toBe(DataType::TYPE_NUMERIC)
            ->and($sheet->getCell('E2')->getValue())->toBe('ivan@example.test')
            ->and($sheet->getCell('G2')->getValue())->toBe('Москва')
            ->and($sheet->getCell('J1')->getValue())->toBe('Telegram')
            ->and($sheet->getCell('J2')->getValue())->toBe('ivan')
            ->and($sheet->getCell('L1')->getValue())->toBe('Дата регистрации');
    } finally {
        $spreadsheet->disconnectWorksheets();
    }
});
