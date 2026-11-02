<?php

declare(strict_types=1);

namespace App\DTO\Tbank\Requests;

use App\Support\Values\Money;
use InvalidArgumentException;

final readonly class TbankReceiptDto
{
    /** @param list<TbankReceiptItemDto> $items */
    public function __construct(
        public string $taxation,
        public array $items,
        public ?string $email = null,
        public ?string $phone = null,
    ) {
        if (! in_array($taxation, ['osn', 'usn_income', 'usn_income_outcome', 'esn', 'patent'], true)) {
            throw new InvalidArgumentException('Некорректная система налогообложения чека.');
        }

        if (($email === null && $phone === null)
            || ($email !== null && (strlen($email) > 64 || filter_var($email, FILTER_VALIDATE_EMAIL) === false))
            || ($phone !== null && preg_match('/^\+[0-9]{1,19}$/D', $phone) !== 1)) {
            throw new InvalidArgumentException('Для чека укажите корректный email или телефон.');
        }

        if (! array_is_list($items) || $items === [] || count($items) > 100) {
            throw new InvalidArgumentException('Чек должен содержать от 1 до 100 позиций.');
        }

        foreach ($items as $item) {
            if (! $item instanceof TbankReceiptItemDto) {
                throw new InvalidArgumentException('Позиции чека должны быть объектами TbankReceiptItemDto.');
            }
        }
    }

    public function total(): Money
    {
        $total = Money::fromMinor(0);

        foreach ($this->items as $item) {
            $total = $total->add($item->total());
        }

        return $total;
    }

    public function toArray(): array
    {
        return array_filter([
            'Taxation' => $this->taxation,
            'Items' => array_map(static fn (TbankReceiptItemDto $item): array => $item->toArray(), $this->items),
            'Email' => $this->email,
            'Phone' => $this->phone,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
