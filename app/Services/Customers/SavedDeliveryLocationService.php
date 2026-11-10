<?php

declare(strict_types=1);

namespace App\Services\Customers;

use App\DTO\Cdek\CdekDeliveryPointResponseDto;
use App\Models\Address;
use App\Models\City;
use App\Models\SavedDeliveryPoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class SavedDeliveryLocationService
{
    /**
     * Сохраняет адрес пользователя без дублей и выбирает его по умолчанию.
     */
    public function rememberAddress(User $user, array $data): Address
    {
        return DB::transaction(function () use ($user, $data): Address {
            $this->lockUser($user);
            $values = ['city_id' => (int) $data['city_id']];

            foreach (['street', 'house', 'building', 'apartment', 'entrance', 'floor', 'intercom'] as $field) {
                $value = trim((string) ($data[$field] ?? ''));
                $values[$field] = $value === '' ? null : $value;
            }

            $address = $user->addresses()->get()->first(function (Address $address) use ($values): bool {
                foreach ($values as $field => $value) {
                    if (mb_strtolower(trim((string) $address->{$field})) !== mb_strtolower(trim((string) $value))) {
                        return false;
                    }
                }

                return true;
            });

            $user->addresses()->update(['is_default' => false]);
            $address ??= new Address(['user_id' => $user->getKey()]);
            $address->fill($values + ['is_default' => true, 'last_used_at' => now()])->save();
            $this->removeOverflow($user->addresses()->getQuery(), $address->getKey());

            return $address;
        });
    }

    /**
     * Сохраняет пункт СДЭК пользователя и выбирает его по умолчанию.
     */
    public function rememberDeliveryPoint(User $user, City $city, CdekDeliveryPointResponseDto $point): SavedDeliveryPoint
    {
        return DB::transaction(function () use ($user, $city, $point): SavedDeliveryPoint {
            $this->lockUser($user);
            $query = $user->savedDeliveryPoints();
            $saved = (clone $query)->firstOrNew(['code' => $point->code]);
            $query->update(['is_default' => false]);
            $saved->fill([
                'city_id' => $city->getKey(),
                'type' => $point->type,
                'address' => $point->address,
                'work_time' => $point->workTime,
                'is_default' => true,
                'last_used_at' => now(),
            ])->save();
            $this->removeOverflow($user->savedDeliveryPoints()->getQuery(), $saved->getKey());

            return $saved;
        });
    }

    /**
     * Выбирает сохранённый адрес по умолчанию и обновляет город пользователя.
     */
    public function selectAddress(User $user, int $id): Address
    {
        return DB::transaction(function () use ($user, $id): Address {
            $this->lockUser($user);
            $address = $user->addresses()->findOrFail($id);
            $user->addresses()->update(['is_default' => false]);
            $address->update(['is_default' => true]);
            $user->update(['city_id' => $address->city_id]);

            return $address;
        });
    }

    /**
     * Удаляет адрес пользователя и при необходимости выбирает другой по умолчанию.
     */
    public function deleteAddress(User $user, int $id): void
    {
        DB::transaction(function () use ($user, $id): void {
            $this->lockUser($user);
            $address = $user->addresses()->findOrFail($id);
            $active = $address->is_default;
            $address->delete();

            if ($active) {
                $next = $this->orderByRecent($user->addresses()->getQuery())->first();
                $next?->update(['is_default' => true]);
                $user->update(['city_id' => $next?->city_id]);
            }
        });
    }

    /**
     * Выбирает сохранённый пункт СДЭК пользователя по умолчанию.
     */
    public function selectDeliveryPoint(User $user, int $id): void
    {
        DB::transaction(function () use ($user, $id): void {
            $this->lockUser($user);
            $point = $user->savedDeliveryPoints()->findOrFail($id);
            $user->savedDeliveryPoints()->update(['is_default' => false]);
            $point->update(['is_default' => true]);
        });
    }

    /**
     * Удаляет сохранённый пункт СДЭК и при необходимости выбирает другой по умолчанию.
     */
    public function deleteDeliveryPoint(User $user, int $id): void
    {
        DB::transaction(function () use ($user, $id): void {
            $this->lockUser($user);
            $point = $user->savedDeliveryPoints()->findOrFail($id);
            $active = $point->is_default;
            $point->delete();

            if ($active) {
                $this->orderByRecent($user->savedDeliveryPoints()->getQuery())->first()?->update(['is_default' => true]);
            }
        });
    }

    private function lockUser(User $user): void
    {
        User::query()
            ->whereKey($user->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function orderByRecent(Builder $query): Builder
    {
        return $query->orderByRaw('COALESCE(last_used_at, created_at) DESC')->orderByDesc('id');
    }

    private function removeOverflow(Builder $query, int $keepId): void
    {
        $ids = $this->orderByRecent(clone $query)->whereKeyNot($keepId)->skip((int) config('orders.saved_locations_limit') - 1)->pluck('id');

        if ($ids->isNotEmpty()) {
            $query->whereKey($ids)->delete();
        }
    }
}
