<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Actions;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Seo\Events\SeoCacheRefreshed;

final readonly class RefreshSeoCacheAction
{
    public function __construct(
        private CacheRepository $cache,
    ) {}

    public function handle(): ActionResult
    {
        if ($result = corexis_authorization_result('seo.sitemap.generate')) {
            return $result;
        }

        $cacheKey = (string) config('seo.cache.prefix', 'seo').':'.(string) config('seo.sitemap.cache_key', 'sitemap');

        $this->cache->forget($cacheKey);

        event(new SeoCacheRefreshed($cacheKey));

        return ActionResult::success(
            message: __('SEO cache je osvježen.'),
            data: ['cache_key' => $cacheKey],
        );
    }
}
