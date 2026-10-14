<?php

declare(strict_types=1);

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Pages\ManageSettings;
use App\Filament\Resources\Articles\ArticleResource;
use App\Filament\Resources\Categories\CategoryResource;
use App\Filament\Resources\Cities\CityResource;
use App\Filament\Resources\Faqs\FaqResource;
use App\Filament\Resources\HomeSlides\HomeSlideResource;
use App\Filament\Resources\News\NewsResource;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Pages\PageResource;
use App\Filament\Resources\Products\ProductResource;
use App\Filament\Resources\Promos\PromoResource;
use App\Filament\Resources\Seo\SeoResource;
use App\Filament\Resources\Users\UserResource;
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
            ->colors([
                'primary' => Color::Amber,
            ])
            ->brandLogo(asset('assets/images/logo/logo-default.svg'))
            ->darkModeBrandLogo(asset('assets/images/logo/logo-white.svg'))
            ->unsavedChangesAlerts()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->navigation(fn (NavigationBuilder $builder): NavigationBuilder => $this->navigation($builder))
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
                    ...Dashboard::getNavigationItems(),
                    ...UserResource::getNavigationItems(),
                    ...OrderResource::getNavigationItems(),
                ]),
            NavigationGroup::make('Продукция')
                ->items([
                    ...CategoryResource::getNavigationItems(),
                    ...ProductResource::getNavigationItems(),
                ]),
            NavigationGroup::make('Контент')
                ->items([
                    ...HomeSlideResource::getNavigationItems(),
                    ...PromoResource::getNavigationItems(),
                    ...NewsResource::getNavigationItems(),
                    ...ArticleResource::getNavigationItems(),
                    ...FaqResource::getNavigationItems(),
                    ...PageResource::getNavigationItems(),
                ]),
            NavigationGroup::make('Настройки')
                ->items([
                    ...CityResource::getNavigationItems(),
                    ...SeoResource::getNavigationItems(),
                    ...ManageSettings::getNavigationItems(),
                ]),
        ]);
    }
}
