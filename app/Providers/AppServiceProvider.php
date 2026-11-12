<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Article;
use App\Models\Category;
use App\Models\City;
use App\Models\Faq;
use App\Models\HomeSlide;
use App\Models\News;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Promo;
use App\Models\Review;
use App\Models\Seo;
use App\Models\Setting;
use App\Models\ShippingBox;
use App\Models\User;
use App\Policies\Filament\AdminResourcePolicy;
use App\Policies\Filament\ReadOnlyResourcePolicy;
use App\Repositories\Products\CatalogRepository;
use App\Services\Cart\CartService;
use App\Services\Cities\CurrentCityService;
use App\Services\Favorites\FavoriteService;
use App\Services\Telegram\TelegramConnectionService;
use App\Support\Email\EmailNormalizer;
use App\Support\Seo\SeoManager;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use SergiX44\Nutgram\Nutgram;

final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(Nutgram::class, static function (): Nutgram {
            $bot = new Nutgram((string) config('telegram.bot_token'));
            $bot->onMessage([app(TelegramConnectionService::class), 'handleMessage']);

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
        TextInput::configureUsing(static function (TextInput $component): void {
            $component->trim(static fn (TextInput $component): bool => ! $component->isPassword());
        });
        Textarea::configureUsing(static function (Textarea $component): void {
            $component->trim();
        });

        foreach ([Article::class, Category::class, City::class, Faq::class, HomeSlide::class, News::class, Page::class, ProductAttribute::class, Promo::class, Review::class, Seo::class, Setting::class, ShippingBox::class] as $model) {
            Gate::policy($model, AdminResourcePolicy::class);
        }

        foreach ([Product::class, User::class] as $model) {
            Gate::policy($model, ReadOnlyResourcePolicy::class);
        }

        Paginator::useBootstrapFive();

        Password::defaults(
            static fn (): Password => Password::min(8)
                ->max(50)
                ->mixedCase()
                ->numbers(),
        );

        RateLimiter::for(
            'login',
            static fn (Request $request): Limit => Limit::perMinute(5)->by(
                EmailNormalizer::normalize($request->string('email')->toString()).'|'.$request->ip(),
            ),
        );

        RateLimiter::for(
            'password-reset',
            static fn (Request $request): Limit => Limit::perMinute(3)->by(
                EmailNormalizer::normalize($request->string('email')->toString()).'|'.$request->ip(),
            ),
        );
    }
}
