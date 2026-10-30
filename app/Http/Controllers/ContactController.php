<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class ContactController extends Controller
{
    public function __invoke(): View
    {
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Контакты'), route('contacts')),
        );

        seo()->forRoute(
            routeName: 'contacts',
            fallbackTitle: __('Контакты'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        return view('contacts.index', compact('breadcrumbs'));
    }
}
