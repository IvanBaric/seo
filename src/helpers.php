<?php

declare(strict_types=1);

use IvanBaric\Seo\Services\SeoManager;

if (! function_exists('seo')) {
    function seo(): SeoManager
    {
        return app(SeoManager::class);
    }
}
