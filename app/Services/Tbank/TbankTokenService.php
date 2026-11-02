<?php

declare(strict_types=1);

namespace App\Services\Tbank;

use App\Services\Tbank\Exceptions\TbankApiException;
use InvalidArgumentException;

final class TbankTokenService
{
    public function generate(array $data): string
    {
        $password = config('tbank.password');

        if (! is_string($password) || $password === '') {
            throw TbankApiException::configuration('password');
        }

        unset($data['Token']);
        $data['Password'] = $password;
        $data = array_filter($data, static fn (mixed $value): bool => $value !== null && ! is_array($value));
        ksort($data, SORT_STRING);
        $values = [];

        foreach ($data as $value) {
            if (! is_string($value) && ! is_int($value) && ! is_bool($value)) {
                throw new InvalidArgumentException('Некорректный тип параметра для подписи Т-Банка.');
            }

            $values[] = is_bool($value) ? ($value ? 'true' : 'false') : (string) $value;
        }

        return hash('sha256', implode('', $values));
    }

    public function verify(array $data): bool
    {
        $token = $data['Token'] ?? null;

        if (! is_string($token) || preg_match('/^[a-f0-9]{64}$/D', $token) !== 1) {
            return false;
        }

        try {
            return hash_equals($this->generate($data), $token);
        } catch (InvalidArgumentException) {
            return false;
        }
    }
}
