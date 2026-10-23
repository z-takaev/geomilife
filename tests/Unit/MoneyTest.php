<?php

declare(strict_types=1);

use App\Support\Values\Money;

it('создает денежную сумму из рублей и копеек', function (int|string $rubles, int $minorAmount): void {
    $money = Money::fromRubles($rubles);

    expect($money->minorAmount())->toBe($minorAmount)
        ->and($money->toRubles())->toBe(number_format($minorAmount / 100, 2, '.', ''));
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
