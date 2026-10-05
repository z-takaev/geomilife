<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Promo;
use Illuminate\Contracts\View\View;

final class PromoController extends Controller
{
    public function index(): View
    {
        return view('promos.index');
    }

    public function show(Promo $promo): View
    {
        return view('promos.show', compact('promo'));
    }
}
