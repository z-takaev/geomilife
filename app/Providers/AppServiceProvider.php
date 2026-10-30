<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\Products\CatalogRepository;
use App\Services\Cart\CartService;
use App\Services\Cities\CurrentCityService;
use App\Services\Favorites\FavoriteService;
use App\Services\Telegram\TelegramService;
use App\Support\Seo\SeoManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use SergiX44\Nutgram\Nutgram;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Nutgram::class, static function (): Nutgram {
            $bot = new Nutgram((string) config('telegram.bot_token'));
            $bot->onMessage([app(TelegramService::class), 'handleMessage']);

            return $bot;
        });
        $this->app->scoped(FavoriteService::class);
        $this->app->scoped(CartService::class);
        $this->app->scoped(SeoManager::class);
        $this->app->scoped(CatalogRepository::class);
        $this->app->scoped(CurrentCityService::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        Password::defaults(
            static fn (): Password => Password::min(8)
                ->max(30)
                ->mixedCase()
                ->numbers(),
        );

        RateLimiter::for(
            'login',
            static fn (Request $request): Limit => Limit::perMinute(5)->by(
                Str::lower($request->string('email')->toString()).'|'.$request->ip(),
            ),
        );

        RateLimiter::for(
            'password-reset',
            static fn (Request $request): Limit => Limit::perMinute(3)->by(
                Str::lower($request->string('email')->toString()).'|'.$request->ip(),
            ),
        );
    }
}
