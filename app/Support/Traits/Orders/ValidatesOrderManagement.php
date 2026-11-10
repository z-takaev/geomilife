<?php

declare(strict_types=1);

namespace App\Support\Traits\Orders;

use App\Enums\FilamentUserRoleEnum;
use App\Models\FilamentUser;
use App\Models\Order;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

trait ValidatesOrderManagement
{
    private function assertStaff(FilamentUser $actor): void
    {
        if (! $actor->exists || ! in_array($actor->role, [FilamentUserRoleEnum::ADMIN, FilamentUserRoleEnum::MANAGER], true)) {
            throw new AuthorizationException;
        }
    }

    private function assertNotCancelling(Order $order): void
    {
        if ($order->cancellation_state !== null) {
            throw ValidationException::withMessages(['order' => 'Изменение заказа во время отмены недоступно.']);
        }
    }
}
