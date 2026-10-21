<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class ProfileController extends Controller
{
    public function __invoke(): View
    {
        seo()->forRoute(
            routeName: 'profile.show',
            fallbackTitle: __('Профиль'),
        )->schema(BreadcrumbSchema::make([
            __('Главная') => route('home'),
            __('Профиль') => route('profile.show'),
        ]));

        return view('profile.show');
    }
}
