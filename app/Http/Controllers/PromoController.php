<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Promo;
use Illuminate\Contracts\View\View;

final class PromoController extends Controller
{
    public function index(): View
    {
        $promos = Promo::query()
            ->with('media')
            ->where('is_active', true)
            ->latest('start_at')
            ->latest('id')
            ->paginate((int) config('pagination.per_page.promos'));

        return view('promos.index', compact('promos'));
    }

    public function show(Promo $promo): View
    {
        abort_unless($promo->is_active, 404);

        return view('promos.show', compact('promo'));
    }
}
