<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Contracts\View\View;

final class ArticleController extends Controller
{
    public function index(): View
    {
        $articles = Article::query()
            ->with('media')
            ->where('is_active', true)
            ->latest('published_at')
            ->latest('id')
            ->paginate((int) config('pagination.per_page.articles'));

        return view('articles.index', compact('articles'));
    }

    public function show(Article $article): View
    {
        abort_unless($article->is_active, 404);

        return view('articles.show', compact('article'));
    }
}
