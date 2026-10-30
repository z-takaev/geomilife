<?php

declare(strict_types=1);

use App\Support\Values\Money;

it('создает денежную сумму из рублей и копеек', function (int|string $rubles, int $minorAmount): void {
    $money = Money::fromRubles($rubles);

    expect($money->minorAmount())->toBe($minorAmount)
        ->and($money->toRubles())->toBe(sprintf('%d.%02d', intdiv($minorAmount, 100), $minorAmount % 100));
})->with([
    'целые рубли' => [1250, 125000],
    'рубли с одной цифрой копеек' => ['1250,5', 125050],
    'рубли с двумя цифрами копеек' => ['1250.55', 125055],
    'ноль' => ['0', 0],
]);

it('отклоняет некорректную денежную сумму', function (int|string $rubles): void {
    expect(fn (): Money => Money::fromRubles($rubles))->toThrow(InvalidArgumentException::class);
})->with([
    'отрицательное значение' => [-1],
    'больше двух знаков после запятой' => ['10.001'],
    'нечисловое значение' => ['сто'],
]);

it('складывает, умножает и сравнивает денежные суммы', function (): void {
    $result = Money::fromRubles('125.50')
        ->add(Money::fromRubles('24.50'))
        ->multiply(2);

    expect($result->equals(Money::fromRubles(300)))->toBeTrue();
});

it('рассчитывает скидку с округлением до копейки', function (): void {
    expect(Money::fromRubles('99.99')->discountedBy(15)->toRubles())->toBe('84.99');
});

it('форматирует цену целым количеством рублей', function (): void {
    expect(Money::fromRubles('1499.50')->format())->toBe('1 500 ₽');
});

it('сохраняет точность предельной денежной суммы', function (): void {
    $rubles = sprintf('%d.%02d', intdiv(PHP_INT_MAX, 100), PHP_INT_MAX % 100);
    expect(Money::fromRubles($rubles)->minorAmount())->toBe(PHP_INT_MAX)
        ->and(Money::fromMinor(PHP_INT_MAX)->toRubles())->toBe($rubles);
});

it('отклоняет переполнение при создании, сложении и умножении', function (): void {
    expect(fn () => Money::fromRubles((string) PHP_INT_MAX))->toThrow(OverflowException::class);
    expect(fn () => Money::fromMinor(PHP_INT_MAX)->add(Money::fromMinor(1)))->toThrow(OverflowException::class);
    expect(fn () => Money::fromMinor(PHP_INT_MAX)->multiply(2))->toThrow(OverflowException::class);
});

it('отклоняет отрицательную сумму и множитель', function (): void {
    expect(fn () => Money::fromMinor(-1))->toThrow(InvalidArgumentException::class);
    expect(fn () => Money::fromRubles(100)->multiply(-1))->toThrow(InvalidArgumentException::class);
});

it('возвращает null при безопасном разборе неверной суммы', function (string $value): void {
    expect(Money::tryFromRubles($value))->toBeNull();
})->with(['invalid', '-1', '10.001', '999999999999999999999999']);

it('обрабатывает крайние скидки и отклоняет проценты вне диапазона', function (): void {
    $money = Money::fromRubles('99.99');
    expect($money->discountedBy(0)->toRubles())->toBe('99.99')
        ->and($money->discountedBy(100)->toRubles())->toBe('0.00')
        ->and($money->multiply(0)->toRubles())->toBe('0.00');
    expect(fn () => $money->discountedBy(-1))->toThrow(InvalidArgumentException::class);
    expect(fn () => $money->discountedBy(101))->toThrow(InvalidArgumentException::class);
});

it('форматирует максимальную сумму без переполнения', function (): void {
    expect(Money::fromMinor(PHP_INT_MAX)->format())->toBe('92 233 720 368 547 758 ₽');
});
