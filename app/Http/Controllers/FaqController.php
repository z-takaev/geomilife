<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Contracts\View\View;

final class FaqController extends Controller
{
    public function index(): View
    {
        $faqs = Faq::cached();

        return view('faqs.index', compact('faqs'));
    }
}
