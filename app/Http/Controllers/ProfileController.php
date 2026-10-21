<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class ProfileController extends Controller
{
    public function __invoke(): View
    {
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Профиль'), route('profile.show')),
        );

        seo()->forRoute(
            routeName: 'profile.show',
            fallbackTitle: __('Профиль'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        return view('profile.show', compact('breadcrumbs'));
    }
}
