<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Data;

use DateTimeInterface;

final readonly class SitemapUrlData
{
    /**
     * @param  array<int, AlternateUrlData>  $alternates
     */
    public function __construct(
        public string $url,
        public ?DateTimeInterface $lastmod = null,
        public array $alternates = [],
    ) {}
}
