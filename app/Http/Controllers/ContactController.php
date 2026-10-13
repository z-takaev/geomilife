<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\SeoRoute;
use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class ContactController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function __invoke(): View
    {
        $seo = $this->seoService->forRoute(SeoRoute::Contacts);

        return view('contacts.index', compact('seo'));
    }
}
