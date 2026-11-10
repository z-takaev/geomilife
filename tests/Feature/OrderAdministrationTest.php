<?php

declare(strict_types=1);

use App\Actions\Orders\ExportOrdersAction;
use App\Enums\FilamentUserRoleEnum;
use App\Filament\Resources\Cities\Pages\ListCities;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\City;
use App\Models\Order;
use App\Models\OrderDocument;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;

require_once __DIR__.'/../Support/FulfillmentFixtures.php';

function fulfillmentSpreadsheet(string $contents): Spreadsheet
{
    $directory = storage_path('framework/testing');
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    $path = $directory.'/fulfillment-'.Str::uuid().'.xlsx';
    file_put_contents($path, $contents);
    try {
        return IOFactory::load($path);
    } finally {
        unlink($path);
    }
}

function fulfillmentStreamedContents(StreamedResponse $response): string
{
    ob_start();
    try {
        $response->sendContent();

        return (string) ob_get_contents();
    } finally {
        ob_end_clean();
    }
}

beforeEach(function (): void {
    Http::preventStrayRequests();
    Filament::setCurrentPanel('admin');
});

it('позволяет сохранять изменения данных оформленного заказа через модель', function (): void {
    $order = fulfillmentOrder();

    $order->update(['customer_name' => 'Новое имя', 'comment' => 'Новый комментарий']);

    expect($order->fresh())
        ->customer_name->toBe('Новое имя')
        ->comment->toBe('Новый комментарий');
});

it('выгружает один заказ одной строкой с семнадцатью колонками и правильными типами', function (): void {
    $this->actingAs(fulfillmentEmployee(), 'filament');
    $order = fulfillmentOrder(['number' => '0000123', 'comment' => '=HYPERLINK("https://invalid.test")']);
    fulfillmentOrderItem($order);
    fulfillmentOrderItem($order, ['product_variant_id' => 2, 'sku' => 'SECOND']);
    $order->payments()->create([
        'provider' => 'TBANK', 'transaction_id' => 'ATTEMPT-1', 'amount' => $order->total,
        'status' => 'PAID', 'payload' => [],
    ]);
    $order->payments()->create([
        'provider' => 'TBANK', 'transaction_id' => 'ATTEMPT-2', 'amount' => $order->total,
        'status' => 'FAILED', 'payload' => [],
    ]);
    $response = app(ExportOrdersAction::class)->handle(Order::query()->whereKey($order->id));
    $spreadsheet = fulfillmentSpreadsheet(fulfillmentStreamedContents($response));
    try {
        $sheet = $spreadsheet->getActiveSheet();
        expect($spreadsheet->getSheetCount())->toBe(1)
            ->and($sheet->getTitle())->toBe('Заказы')
            ->and($sheet->getHighestDataRow())->toBe(2)
            ->and($sheet->getHighestDataColumn())->toBe('Q')
            ->and($sheet->getCell('A2')->getValue())->toBe('0000123')
            ->and($sheet->getCell('C2')->getValue())->toBe('Покупатель из снимка')
            ->and($sheet->getCell('D2')->getDataType())->toBe(DataType::TYPE_STRING)
            ->and($sheet->getCell('E2')->getValue())->toBe('snapshot@example.test')
            ->and($sheet->getCell('F2')->getValue())->toBe('Москва')
            ->and($sheet->getCell('L2')->getValue())->toBe(1200)
            ->and($sheet->getCell('M2')->getValue())->toBe(350)
            ->and($sheet->getCell('N2')->getValue())->toBe(1550)
            ->and($sheet->getCell('N2')->getDataType())->toBe(DataType::TYPE_NUMERIC)
            ->and($sheet->getCell('Q2')->getValue())->toBe($order->comment)
            ->and($sheet->getCell('Q2')->getDataType())->toBe(DataType::TYPE_STRING);
    } finally {
        $spreadsheet->disconnectWorksheets();
    }
});

it('экспортирует все результаты фильтров и поиска со всех страниц в выбранной сортировке', function (): void {
    $this->actingAs(fulfillmentEmployee(), 'filament');
    $matching = collect(range(1, 31))->map(fn (int $index): Order => fulfillmentOrder([
        'number' => sprintf('FILTER-%03d', $index), 'status' => 'CONFIRMED',
        'delivery_method' => 'COURIER',
    ]));
    fulfillmentOrder(['number' => 'FILTER-WRONG-STATUS', 'status' => 'NEW', 'delivery_method' => 'COURIER']);
    fulfillmentOrder(['number' => 'FILTER-WRONG-DELIVERY', 'status' => 'CONFIRMED', 'delivery_method' => 'PICKUP']);
    fulfillmentOrder(['number' => 'OTHER-NUMBER', 'status' => 'CONFIRMED', 'delivery_method' => 'COURIER']);

    $component = Livewire::test(ListOrders::class)
        ->filterTable('status', 'CONFIRMED')
        ->filterTable('delivery_method', 'COURIER')
        ->searchTable('FILTER-')
        ->sortTable('number', 'desc')
        ->set('tableRecordsPerPage', 10)
        ->set('selectedTableRecords', [$matching->first()->id])
        ->callAction('exportExcel')
        ->assertFileDownloaded();

    $spreadsheet = fulfillmentSpreadsheet(base64_decode($component->effects['download']['content'], true));
    try {
        $sheet = $spreadsheet->getActiveSheet();
        expect($sheet->getHighestDataRow())->toBe(32);
        $numbers = array_column($sheet->rangeToArray('A2:A32'), 0);
        expect($numbers)->toBe($matching->pluck('number')->reverse()->values()->all());
    } finally {
        $spreadsheet->disconnectWorksheets();
    }
});

it('выгружает пустую выборку файлом с заголовками без чужих строк', function (): void {
    $this->actingAs(fulfillmentEmployee(), 'filament');
    fulfillmentOrder();
    $response = app(ExportOrdersAction::class)->handle(Order::query()->whereRaw('1 = 0'));
    $spreadsheet = fulfillmentSpreadsheet(fulfillmentStreamedContents($response));
    try {
        expect($spreadsheet->getActiveSheet()->getHighestDataRow())->toBe(1)
            ->and($spreadsheet->getActiveSheet()->getHighestDataColumn())->toBe('Q');
    } finally {
        $spreadsheet->disconnectWorksheets();
    }
});

it('ставит города с курьером или самовывозом первыми до пагинации', function (): void {
    $this->actingAs(fulfillmentEmployee(), 'filament');
    foreach (range(1, 15) as $index) {
        City::query()->create(['cdek_code' => 9000 + $index, 'name' => sprintf('А-%02d', $index), 'is_active' => true]);
    }
    $courier = City::query()->create(['cdek_code' => 9101, 'name' => 'Я-курьер', 'is_active' => true, 'is_courier_available' => true]);
    $pickup = City::query()->create(['cdek_code' => 9102, 'name' => 'Ю-самовывоз', 'is_active' => true, 'is_pickup_available' => true]);
    $both = City::query()->create(['cdek_code' => 9103, 'name' => 'Э-оба', 'is_active' => true, 'is_courier_available' => true, 'is_pickup_available' => true]);

    $component = Livewire::test(ListCities::class)->set('tableRecordsPerPage', 10);
    $records = $component->instance()->getTableRecords();
    expect($records->take(3)->modelKeys())->toBe([$both->id, $pickup->id, $courier->id]);

    $component->sortTable('name')->assertCanSeeTableRecords(
        City::query()->orderBy('name')->limit(10)->get(), inOrder: true,
    );
});

it('сохраняет приоритет городов после фильтра и поиска', function (): void {
    $this->actingAs(fulfillmentEmployee(), 'filament');
    $regular = City::query()->create(['cdek_code' => 9201, 'name' => 'Город А', 'is_active' => true]);
    $priority = City::query()->create(['cdek_code' => 9202, 'name' => 'Город Я', 'is_active' => true, 'is_pickup_available' => true]);
    City::query()->create(['cdek_code' => 9203, 'name' => 'Другой', 'is_active' => true, 'is_pickup_available' => true]);

    Livewire::test(ListCities::class)->filterTable('is_active', true)->searchTable('Город')
        ->assertCanSeeTableRecords([$priority, $regular], inOrder: true);
});

it('скачивает внутреннюю накладную сотруднику без файлов на дисках приложения', function (FilamentUserRoleEnum $role): void {
    Storage::fake('local');
    Storage::fake('public');
    $order = fulfillmentOrder();
    fulfillmentOrderItem($order);
    $this->actingAs(fulfillmentEmployee($role), 'filament');

    $response = $this->get(route('admin.orders.packing-slip', $order));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('content-disposition'))->toContain('attachment')
        ->and($response->streamedContent())->toStartWith('%PDF')
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([]);
})->with([FilamentUserRoleEnum::ADMIN, FilamentUserRoleEnum::MANAGER]);

it('скачивает документы СДЭК сотруднику через сервер без публичного редиректа и хранения', function (string $type, string $route, string $endpoint): void {
    Storage::fake('local');
    Storage::fake('public');
    $order = fulfillmentOrder();
    $uuid = '22222222-2222-4222-8222-222222222222';
    OrderDocument::query()->create(['order_id' => $order->id, 'type' => $type, 'uuid' => $uuid, 'state' => 'READY']);
    $this->actingAs(fulfillmentEmployee(), 'filament');
    config(['services.cdek.base_url' => 'https://cdek.test', 'services.cdek.client_id' => 'test-client', 'services.cdek.client_secret' => 'test-secret']);
    Http::fake([
        'cdek.test/v2/oauth/token*' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'cdek.test/v2/print/'.$endpoint.'/'.$uuid => Http::response([
            'entity' => ['uuid' => $uuid, 'url' => 'https://private-cdek.test/document.pdf',
                'statuses' => [['code' => 'READY', 'name' => 'Готов', 'date_time' => '2026-11-07T12:00:00+03:00']]],
        ]),
        'cdek.test/v2/print/'.$endpoint.'/'.$uuid.'.pdf' => Http::response('%PDF-1.4 test', 200, ['Content-Type' => 'application/pdf']),
    ]);

    $response = $this->get(route($route, $order));

    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect($response->headers->get('location'))->toBeNull()
        ->and($response->headers->get('content-disposition'))->toContain('attachment')
        ->and($response->streamedContent())->toBe('%PDF-1.4 test')
        ->and(Storage::disk('local')->allFiles())->toBe([])
        ->and(Storage::disk('public')->allFiles())->toBe([]);
})->with([
    'квитанция' => ['WAYBILL', 'admin.orders.cdek-waybill', 'orders'],
    'наклейки' => ['BARCODES', 'admin.orders.cdek-barcodes', 'barcodes'],
]);

it('запрещает владельцу заказа и гостю прямое скачивание всех служебных документов', function (string $route): void {
    $user = User::query()->create(['first_name' => 'Клиент', 'email' => 'documents@example.test', 'password' => 'password']);
    $order = fulfillmentOrder(['user_id' => $user->id]);

    $this->getJson(route($route, $order))->assertUnauthorized();
    $this->actingAs($user)->getJson(route($route, $order))->assertUnauthorized();
    Http::assertNothingSent();
})->with(['admin.orders.packing-slip', 'admin.orders.cdek-waybill', 'admin.orders.cdek-barcodes']);
