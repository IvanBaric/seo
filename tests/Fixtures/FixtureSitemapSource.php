<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Fixtures;

use IvanBaric\Seo\Contracts\SitemapSource;
use IvanBaric\Seo\Data\SitemapUrlData;
use IvanBaric\Seo\Tests\Fixtures\Models\SeoFixtureModel;

final class FixtureSitemapSource implements SitemapSource
{
    public function sitemapModels(): iterable
    {
        return collect([
            new SeoFixtureModel(['indexed' => false]),
            new SitemapUrlData(url: 'https://example.test/custom-source'),
        ]);
    }
}
