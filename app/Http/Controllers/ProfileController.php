<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Seo\SeoService;
use Illuminate\Contracts\View\View;

final class ProfileController extends Controller
{
    public function __construct(private readonly SeoService $seoService) {}

    public function __invoke(): View
    {
        $seo = $this->seoService->forRoute(
            routeName: 'profile.show',
            fallbackTitle: __('Профиль'),
        );

        return view('profile.show', compact('seo'));
    }
}
