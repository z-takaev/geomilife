<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Contracts\View\View;

final class ArticleController extends Controller
{
    public function index(): View
    {
        return view('articles.index');
    }

    public function show(Article $article): View
    {
        return view('articles.show', compact('article'));
    }
}
