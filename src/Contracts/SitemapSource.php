<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Contracts;

use Illuminate\Support\Collection;

interface SitemapSource
{
    /**
     * @return Collection<int, \Illuminate\Database\Eloquent\Model>
     */
    public function sitemapModels(): Collection;
}
