<?php

declare(strict_types=1);

namespace App\Services\Cdek;

use App\DTO\Cdek\CdekCalculateTariffRequestDto;
use App\DTO\Cdek\CdekCalculateTariffResponseDto;
use App\DTO\Cdek\CdekCityResponseDto;
use App\DTO\Cdek\CdekCreateOrderRequestDto;
use App\DTO\Cdek\CdekDeliveryPointResponseDto;
use App\DTO\Cdek\CdekGetDeliveryPointsRequestDto;
use App\DTO\Cdek\CdekOperationResponseDto;
use App\DTO\Cdek\CdekOrderResponseDto;
use App\DTO\Cdek\CdekPrintResponseDto;
use App\DTO\Cdek\CdekWebhookResponseDto;
use InvalidArgumentException;

final class CdekService
{
    /**
     * Подключает API-клиент СДЭК и преобразователь ответов.
     */
    public function __construct(
        private readonly CdekClient $client,
        private readonly CdekResponseMapper $mapper,
    ) {}

    /**
     * Получить страницу населённых пунктов России.
     *
     * @return list<CdekCityResponseDto>
     */
    public function getCities(int $page = 0, int $size = 1000): array
    {
        if ($page < 0) {
            throw new InvalidArgumentException('Номер страницы не может быть отрицательным.');
        }

        if ($size < 1) {
            throw new InvalidArgumentException('Размер страницы должен быть больше нуля.');
        }

        $cities = $this->client->get('/v2/location/cities', [
            'country_codes' => 'RU',
            'lang' => 'RU',
            'page' => $page,
            'size' => $size,
        ]);

        return $this->mapper->cities($cities);
    }

    /**
     * Получить ПВЗ и постаматы города с учётом ограничений посылки.
     *
     * @return list<CdekDeliveryPointResponseDto>
     */
    public function getDeliveryPoints(CdekGetDeliveryPointsRequestDto $filters): array
    {
        return $this->mapper->deliveryPoints($this->client->get('/v2/deliverypoints', $filters->toArray()));
    }

    /**
     * Рассчитать выбранный тариф, включая дополнительные услуги и итоговую стоимость.
     */
    public function calculateTariff(CdekCalculateTariffRequestDto $delivery, int $tariffCode): CdekCalculateTariffResponseDto
    {
        if ($tariffCode < 1) {
            throw new InvalidArgumentException('Код тарифа должен быть больше нуля.');
        }

        return $this->mapper->calculation($this->client->post('/v2/calculator/tariff', [
            ...$delivery->toArray(),
            'tariff_code' => $tariffCode,
        ]));
    }

    /**
     * Отправить заявку на создание отправления; ACCEPTED ещё не означает успешное создание.
     */
    public function createOrder(CdekCreateOrderRequestDto $order): CdekOperationResponseDto
    {
        return $this->mapper->operation($this->client->post('/v2/orders', $order->toArray()));
    }

    /**
     * Получить отправление по UUID, трек-номер, статус доставки и результат обработки запроса.
     */
    public function getOrder(string $uuid): CdekOrderResponseDto
    {
        return $this->mapper->order($this->client->get('/v2/orders/'.$this->uuid($uuid)));
    }

    /**
     * Получает отправление СДЭК по номеру заказа интернет-магазина.
     */
    public function findOrder(string $number): CdekOrderResponseDto
    {
        return $this->mapper->order($this->client->get('/v2/orders', ['im_number' => $number]));
    }

    /**
     * Отправляет запрос на отмену отправления СДЭК по UUID.
     */
    public function cancelOrder(string $uuid): CdekOperationResponseDto
    {
        return $this->mapper->operation($this->client->delete('/v2/orders/'.$this->uuid($uuid)));
    }

    /**
     * Запустить формирование квитанции одного отправления; ответ содержит UUID задания печати.
     */
    public function createWaybill(string $orderUuid, ?int $copyCount = null): CdekOperationResponseDto
    {
        return $this->mapper->operation($this->client->post('/v2/print/orders', $this->printRequest($orderUuid, $copyCount)));
    }

    /**
     * Получить готовность квитанции и ссылку на PDF по UUID задания печати.
     */
    public function getWaybill(string $printUuid): CdekPrintResponseDto
    {
        return $this->mapper->printDocument($this->client->get('/v2/print/orders/'.$this->uuid($printUuid)));
    }

    /**
     * Скачать готовую квитанцию по UUID задания печати; вернуть бинарное содержимое PDF.
     */
    public function downloadWaybill(string $printUuid): string
    {
        return $this->client->download('/v2/print/orders/'.$this->uuid($printUuid).'.pdf');
    }

    /**
     * Запустить формирование наклеек одного отправления в формате A4.
     */
    public function createBarcodes(string $orderUuid, ?int $copyCount = null): CdekOperationResponseDto
    {
        $data = $this->printRequest($orderUuid, $copyCount);

        $data['format'] = 'A4';

        return $this->mapper->operation($this->client->post('/v2/print/barcodes', $data));
    }

    /**
     * Получить готовность наклеек и ссылку на PDF по UUID задания печати.
     */
    public function getBarcodes(string $printUuid): CdekPrintResponseDto
    {
        return $this->mapper->printDocument($this->client->get('/v2/print/barcodes/'.$this->uuid($printUuid)));
    }

    /**
     * Скачать готовые наклейки по UUID задания печати; вернуть бинарное содержимое PDF.
     */
    public function downloadBarcodes(string $printUuid): string
    {
        return $this->client->download('/v2/print/barcodes/'.$this->uuid($printUuid).'.pdf');
    }

    /**
     * Создать подписку ORDER_STATUS; перед вызовом проверить существующие подписки.
     */
    public function createOrderStatusWebhook(string $url): CdekOperationResponseDto
    {
        if (filter_var($url, FILTER_VALIDATE_URL) === false || ! in_array(parse_url($url, PHP_URL_SCHEME), ['https', 'http'], true)) {
            throw new InvalidArgumentException('Укажите корректный HTTP или HTTPS URL вебхука.');
        }

        return $this->mapper->operation($this->client->post('/v2/webhooks', [
            'type' => 'ORDER_STATUS',
            'url' => $url,
        ]));
    }

    /**
     * Получить действующие подписки перед созданием новой.
     *
     * @return list<CdekWebhookResponseDto>
     */
    public function getWebhooks(): array
    {
        return $this->mapper->webhooks($this->client->get('/v2/webhooks'));
    }

    /** @return array<string, mixed> */
    private function printRequest(string $orderUuid, ?int $copyCount): array
    {
        if ($copyCount !== null && $copyCount < 1) {
            throw new InvalidArgumentException('Количество копий должно быть больше нуля.');
        }

        return array_filter([
            'orders' => [['order_uuid' => $this->uuid($orderUuid)]],
            'copy_count' => $copyCount,
        ], static fn (mixed $value): bool => $value !== null);
    }

    private function uuid(string $uuid): string
    {
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $uuid) !== 1) {
            throw new InvalidArgumentException('Укажите корректный UUID СДЭК.');
        }

        return $uuid;
    }
}
