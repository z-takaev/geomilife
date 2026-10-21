<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Support\Seo\Schemas\BreadcrumbSchema;
use Illuminate\Contracts\View\View;

final class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::cached();
        seo()->forRoute(
            routeName: 'faqs.index',
            fallbackTitle: __('Вопросы и ответы'),
        )->schema(BreadcrumbSchema::make([
            __('Главная') => route('home'),
            __('Вопросы и ответы') => route('faqs.index'),
        ]));

        return view('faqs.index', compact('faqs'));
    }
}
