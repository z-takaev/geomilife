<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Phone\PhoneFormat;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class PhoneNumber implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! PhoneFormat::isValid($value)) {
            $fail(__('Укажите корректный российский номер телефона.'));
        }
    }
}
