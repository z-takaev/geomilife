<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

final class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $siteUrl = rtrim((string) config('app.url'), '/');
        $content = implode("\n", [
            'User-agent: *',
            'Disallow: /admin/',
            '',
            "Sitemap: {$siteUrl}/sitemap.xml",
        ]);

        return response($content)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
