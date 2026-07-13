<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Contracts;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Seo\Data\SitemapUrlData;

interface SitemapSource
{
    /**
     * @return iterable<Model|SitemapUrlData>
     */
    public function sitemapModels(): iterable;
}
