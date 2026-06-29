<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Fixtures;

use Illuminate\Support\Collection;
use IvanBaric\Seo\Contracts\SitemapSource;
use IvanBaric\Seo\Data\SitemapUrlData;

final class FixtureSitemapSource implements SitemapSource
{
    public function sitemapModels(): Collection
    {
        return collect([
            new SitemapUrlData(url: 'https://example.test/custom-source'),
        ]);
    }
}
