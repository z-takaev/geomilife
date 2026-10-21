<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class ContactController extends Controller
{
    public function __invoke(): View
    {
        seo()->forRoute(
            routeName: 'contacts.index',
            fallbackTitle: __('Контакты'),
        )->schema(BreadcrumbSchema::make([
            __('Главная') => route('home'),
            __('Контакты') => route('contacts.index'),
        ]));

        return view('contacts.index');
    }
}
