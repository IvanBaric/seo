<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use IvanBaric\Seo\Data\SitemapUrlData;

interface SitemapSource
{
    /**
     * @return Collection<int, Model|SitemapUrlData>
     */
    public function sitemapModels(): Collection;
}
