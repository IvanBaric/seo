<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use IvanBaric\Seo\Services\SitemapGenerator;

if (
    (bool) config('seo.routes.enabled', true)
    && (bool) config('seo.sitemap.enabled', true)
    && (bool) config('seo.sitemap.route_enabled', true)
) {
    Route::get(config('seo.sitemap.route_path', 'sitemap.xml'), function (SitemapGenerator $generator) {
        return response($generator->generate(), 200, ['Content-Type' => 'application/xml']);
    })->middleware(config('seo.routes.middleware', ['web']))->name('seo.sitemap');
}
