<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Navigation\BreadcrumbItem;
use App\Navigation\Breadcrumbs;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class FaqController extends Controller
{
    public function __invoke(): View
    {
        $breadcrumbs = Breadcrumbs::make(
            BreadcrumbItem::make(__('Вопросы и ответы'), route('faqs')),
        );

        seo()->forRoute(
            routeName: 'faqs',
            fallbackTitle: __('Вопросы и ответы'),
        )->schema(BreadcrumbSchema::make($breadcrumbs));

        $faqs = Faq::cached();

        return view('faqs.index', compact('faqs', 'breadcrumbs'));
    }
}
