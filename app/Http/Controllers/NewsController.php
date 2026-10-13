<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Contracts\View\View;

final class NewsController extends Controller
{
    public function index(): View
    {
        $newsItems = News::query()
            ->with('media')
            ->where('is_active', true)
            ->latest('published_at')
            ->latest('id')
            ->paginate((int) config('pagination.per_page.news'));

        return view('news.index', compact('newsItems'));
    }

    public function show(News $news): View
    {
        abort_unless($news->is_active, 404);

        return view('news.show', compact('news'));
    }
}
