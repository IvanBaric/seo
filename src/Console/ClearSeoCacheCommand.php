<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

final class ClearSeoCacheCommand extends Command
{
    protected $signature = 'seo:clear-cache';

    protected $description = 'Clear SEO package cache entries.';

    public function handle(CacheRepository $cache): int
    {
        $prefix = (string) config('seo.cache.prefix', 'seo');
        $cache->forget($prefix.':'.(string) config('seo.sitemap.cache_key', 'sitemap'));

        $this->info('SEO cache cleared.');

        return self::SUCCESS;
    }
}
