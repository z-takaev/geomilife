<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Cities\CityResource;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\FilamentUsers\FilamentUserResource;
use App\Filament\Resources\HomeSlides\HomeSlideResource;
use App\Filament\Resources\News\NewsResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\ProductAttributes\ProductAttributeResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Promos\PromoResource;
use App\Filament\Resources\Reviews\ReviewResource;
use App\Filament\Resources\Seo\SeoResource;
use App\Filament\Resources\ShippingBoxes\ShippingBoxResource;
use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

final class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->authGuard('filament')
            ->login()
            ->profile(isSimple: false)
            ->userMenuItems([
                'profile' => fn (Action $action): Action => $action->sort(PHP_INT_MAX - 1),
            ])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandLogo(asset('assets/images/logo/logo-default.svg'))
            ->darkModeBrandLogo(asset('assets/images/logo/logo-white.svg'))
            ->brandLogoHeight('2.5rem')
            ->unsavedChangesAlerts()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->navigation($this->navigation(...))
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn (): View => view('filament.components.visit-site-button'),
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    private function navigation(NavigationBuilder $builder): NavigationBuilder
    {
        return $builder->groups([
            NavigationGroup::make()
                ->items([
                    ...self::navigationItems(Dashboard::class),
                    ...self::navigationItems(OrderResource::class),
                    ...self::navigationItems(UserResource::class),
                ]),
            NavigationGroup::make('Продукция')
                ->items([
                    ...self::navigationItems(CategoryResource::class),
                    ...self::navigationItems(ProductResource::class),
                    ...self::navigationItems(ProductAttributeResource::class),
                ]),
            NavigationGroup::make('Контент')
                ->items([
                    ...self::navigationItems(HomeSlideResource::class),
                    ...self::navigationItems(PromoResource::class),
                    ...self::navigationItems(NewsResource::class),
                    ...self::navigationItems(ArticleResource::class),
                    ...self::navigationItems(ReviewResource::class),
                    ...self::navigationItems(FaqResource::class),
                    ...self::navigationItems(PageResource::class),
                ]),
            NavigationGroup::make('Настройки')
                ->items([
                    ...self::navigationItems(CityResource::class),
                    ...self::navigationItems(ShippingBoxResource::class),
                    ...self::navigationItems(FilamentUserResource::class),
                    ...self::navigationItems(SeoResource::class),
                    ...self::navigationItems(ManageSettings::class),
                ]),
        ]);
    }

    private static function navigationItems(string $component): array
    {
        return $component::canAccess() ? $component::getNavigationItems() : [];
    }
}
