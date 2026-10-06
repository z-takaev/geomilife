<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HomeSlide;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __invoke(): View
    {
        $slides = HomeSlide::query()
            ->where('is_active', true)
            ->with('media')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(static fn (HomeSlide $slide): bool => $slide->hasMedia('desktop_image') && $slide->hasMedia('mobile_image'))
            ->values();

        return view('home.index', ['slides' => $slides]);
    }
}
