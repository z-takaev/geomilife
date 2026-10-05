<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Contracts\View\View;

final class NewsController extends Controller
{
    public function index(): View
    {
        return view('news.index');
    }

    public function show(News $news): View
    {
        return view('news.show', compact('news'));
    }
}
