<?php

declare(strict_types=1);

namespace App\Enums\Cdek;

use Filament\Support\Contracts\HasLabel;

enum CdekOrderStatusEnum: string implements HasLabel
{
    case ACCEPTED = 'ACCEPTED';
    case CREATED = 'CREATED';
    case REMOVED = 'REMOVED';
    case RECEIVED_AT_SHIPMENT_WAREHOUSE = 'RECEIVED_AT_SHIPMENT_WAREHOUSE';
    case DELIVERED = 'DELIVERED';
    case NOT_DELIVERED = 'NOT_DELIVERED';
    case READY_FOR_SHIPMENT_IN_SENDER_CITY = 'READY_FOR_SHIPMENT_IN_SENDER_CITY';
    case TAKEN_BY_TRANSPORTER_FROM_SENDER_CITY = 'TAKEN_BY_TRANSPORTER_FROM_SENDER_CITY';
    case SENT_TO_RECIPIENT_CITY = 'SENT_TO_RECIPIENT_CITY';
    case ACCEPTED_IN_RECIPIENT_CITY = 'ACCEPTED_IN_RECIPIENT_CITY';
    case ACCEPTED_AT_RECIPIENT_CITY_WAREHOUSE = 'ACCEPTED_AT_RECIPIENT_CITY_WAREHOUSE';
    case TAKEN_BY_COURIER = 'TAKEN_BY_COURIER';
    case ACCEPTED_AT_PICK_UP_POINT = 'ACCEPTED_AT_PICK_UP_POINT';
    case ACCEPTED_AT_TRANSIT_WAREHOUSE = 'ACCEPTED_AT_TRANSIT_WAREHOUSE';
    case RETURNED_TO_SENDER_CITY_WAREHOUSE = 'RETURNED_TO_SENDER_CITY_WAREHOUSE';
    case RETURNED_TO_TRANSIT_WAREHOUSE = 'RETURNED_TO_TRANSIT_WAREHOUSE';
    case RETURNED_TO_RECIPIENT_CITY_WAREHOUSE = 'RETURNED_TO_RECIPIENT_CITY_WAREHOUSE';
    case READY_FOR_SHIPMENT_IN_TRANSIT_CITY = 'READY_FOR_SHIPMENT_IN_TRANSIT_CITY';
    case TAKEN_BY_TRANSPORTER_FROM_TRANSIT_CITY = 'TAKEN_BY_TRANSPORTER_FROM_TRANSIT_CITY';
    case SENT_TO_TRANSIT_CITY = 'SENT_TO_TRANSIT_CITY';
    case ACCEPTED_IN_TRANSIT_CITY = 'ACCEPTED_IN_TRANSIT_CITY';
    case SENT_TO_SENDER_CITY = 'SENT_TO_SENDER_CITY';
    case ACCEPTED_IN_SENDER_CITY = 'ACCEPTED_IN_SENDER_CITY';
    case ENTERED_TO_TRANSIT_WAREHOUSE = 'ENTERED_TO_TRANSIT_WAREHOUSE';
    case ENTERED_TO_RECIPIENT_CITY_WAREHOUSE = 'ENTERED_TO_RECIPIENT_CITY_WAREHOUSE';
    case ENTERED_TO_PICK_UP_POINT = 'ENTERED_TO_PICK_UP_POINT';
    case IN_CUSTOMS_INTERNATIONAL = 'IN_CUSTOMS_INTERNATIONAL';
    case SHIPPED_TO_DESTINATION = 'SHIPPED_TO_DESTINATION';
    case PASSED_TO_TRANSIT_CARRIER = 'PASSED_TO_TRANSIT_CARRIER';
    case IN_CUSTOMS_LOCAL = 'IN_CUSTOMS_LOCAL';
    case CUSTOMS_COMPLETE = 'CUSTOMS_COMPLETE';
    case POSTOMAT_POSTED = 'POSTOMAT_POSTED';
    case POSTOMAT_SEIZED = 'POSTOMAT_SEIZED';
    case POSTOMAT_RECEIVED = 'POSTOMAT_RECEIVED';
    case INVALID = 'INVALID';

    case READY_FOR_DELIVERY = 'READY_FOR_DELIVERY';
    case ACCEPTED_AT_DELIVERY_WAREHOUSE = 'ACCEPTED_AT_DELIVERY_WAREHOUSE';
    case SENT_TO_DELIVERY_CITY = 'SENT_TO_DELIVERY_CITY';
    case DELETED = 'DELETED';
    case CANCELLED = 'CANCELLED';
    case CANCELED = 'CANCELED';

    public function is(self $type): bool
    {
        return $this === $type;
    }

    public function indicatesShipped(): bool
    {
        return in_array($this, [
            self::RECEIVED_AT_SHIPMENT_WAREHOUSE,
            self::NOT_DELIVERED,
            self::READY_FOR_SHIPMENT_IN_SENDER_CITY,
            self::TAKEN_BY_TRANSPORTER_FROM_SENDER_CITY,
            self::SENT_TO_RECIPIENT_CITY,
            self::ACCEPTED_IN_RECIPIENT_CITY,
            self::ACCEPTED_AT_RECIPIENT_CITY_WAREHOUSE,
            self::TAKEN_BY_COURIER,
            self::ACCEPTED_AT_PICK_UP_POINT,
            self::ACCEPTED_AT_TRANSIT_WAREHOUSE,
            self::READY_FOR_DELIVERY,
            self::ACCEPTED_AT_DELIVERY_WAREHOUSE,
            self::SENT_TO_DELIVERY_CITY,
            self::RETURNED_TO_SENDER_CITY_WAREHOUSE,
            self::RETURNED_TO_TRANSIT_WAREHOUSE,
            self::RETURNED_TO_RECIPIENT_CITY_WAREHOUSE,
            self::READY_FOR_SHIPMENT_IN_TRANSIT_CITY,
            self::TAKEN_BY_TRANSPORTER_FROM_TRANSIT_CITY,
            self::SENT_TO_TRANSIT_CITY,
            self::ACCEPTED_IN_TRANSIT_CITY,
            self::SENT_TO_SENDER_CITY,
            self::ACCEPTED_IN_SENDER_CITY,
            self::ENTERED_TO_TRANSIT_WAREHOUSE,
            self::ENTERED_TO_RECIPIENT_CITY_WAREHOUSE,
            self::ENTERED_TO_PICK_UP_POINT,
            self::IN_CUSTOMS_INTERNATIONAL,
            self::SHIPPED_TO_DESTINATION,
            self::PASSED_TO_TRANSIT_CARRIER,
            self::IN_CUSTOMS_LOCAL,
            self::CUSTOMS_COMPLETE,
            self::POSTOMAT_POSTED,
            self::POSTOMAT_SEIZED,
        ], true);
    }

    public function isDelivered(): bool
    {
        return in_array($this, [self::DELIVERED, self::POSTOMAT_RECEIVED], true);
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::ACCEPTED => __('Принят'),
            self::CREATED => __('Создан'),
            self::REMOVED, self::DELETED, self::CANCELLED, self::CANCELED => __('Отменён'),
            self::RECEIVED_AT_SHIPMENT_WAREHOUSE => __('Принят на складе отправителя'),
            self::DELIVERED => __('Вручен'),
            self::NOT_DELIVERED => __('Не вручен'),
            self::READY_FOR_SHIPMENT_IN_SENDER_CITY => __('Готов к отправке в городе отправителя'),
            self::TAKEN_BY_TRANSPORTER_FROM_SENDER_CITY => __('Передан перевозчику в городе отправителя'),
            self::SENT_TO_RECIPIENT_CITY => __('Отправлен в город получателя'),
            self::ACCEPTED_IN_RECIPIENT_CITY => __('Встречен в городе получателя'),
            self::ACCEPTED_AT_RECIPIENT_CITY_WAREHOUSE => __('Принят на складе доставки'),
            self::TAKEN_BY_COURIER => __('Выдан курьеру на доставку'),
            self::ACCEPTED_AT_PICK_UP_POINT => __('Принят в пункте выдачи'),
            self::ACCEPTED_AT_TRANSIT_WAREHOUSE => __('Принят на транзитном складе'),
            self::RETURNED_TO_SENDER_CITY_WAREHOUSE => __('Возвращён на склад отправителя'),
            self::RETURNED_TO_TRANSIT_WAREHOUSE => __('Возвращён на транзитный склад'),
            self::RETURNED_TO_RECIPIENT_CITY_WAREHOUSE => __('Возвращён на склад доставки'),
            self::READY_FOR_SHIPMENT_IN_TRANSIT_CITY => __('Готов к отправке в транзитном городе'),
            self::TAKEN_BY_TRANSPORTER_FROM_TRANSIT_CITY => __('Передан перевозчику в транзитном городе'),
            self::SENT_TO_TRANSIT_CITY => __('Отправлен в транзитный город'),
            self::ACCEPTED_IN_TRANSIT_CITY => __('Встречен в транзитном городе'),
            self::SENT_TO_SENDER_CITY => __('Отправлен в город отправителя'),
            self::ACCEPTED_IN_SENDER_CITY => __('Встречен в городе отправителя'),
            self::ENTERED_TO_TRANSIT_WAREHOUSE => __('Поступил на транзитный склад'),
            self::ENTERED_TO_RECIPIENT_CITY_WAREHOUSE => __('Поступил на склад доставки'),
            self::ENTERED_TO_PICK_UP_POINT => __('Поступил в пункт выдачи'),
            self::IN_CUSTOMS_INTERNATIONAL => __('Таможенное оформление в стране отправления'),
            self::SHIPPED_TO_DESTINATION => __('Отправлен в страну назначения'),
            self::PASSED_TO_TRANSIT_CARRIER => __('Передан транзитному перевозчику'),
            self::IN_CUSTOMS_LOCAL => __('Таможенное оформление в стране назначения'),
            self::CUSTOMS_COMPLETE => __('Таможенное оформление завершено'),
            self::POSTOMAT_POSTED => __('Заложен в постамат'),
            self::POSTOMAT_SEIZED => __('Изъят из постамата курьером'),
            self::POSTOMAT_RECEIVED => __('Получен из постамата'),
            self::INVALID => __('Некорректный заказ'),
            self::READY_FOR_DELIVERY => __('Готов к доставке'),
            self::ACCEPTED_AT_DELIVERY_WAREHOUSE => __('Принят на складе доставки'),
            self::SENT_TO_DELIVERY_CITY => __('Отправлен в город получателя'),
        };
    }
}
