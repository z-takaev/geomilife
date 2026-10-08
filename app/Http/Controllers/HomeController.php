<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\HomeSlide;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __invoke(): View
    {
        $slides = HomeSlide::cached();

        return view('home.index', compact('slides'));
    }
}
