<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

interface SitemapSource
{
    /**
     * @return Collection<int, Model>
     */
    public function sitemapModels(): Collection;
}
