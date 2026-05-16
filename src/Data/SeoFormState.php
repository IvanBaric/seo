<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Data;

final readonly class SeoFormState
{
    public function __construct(
        public ?string $title,
        public ?string $description,
        public ?string $canonicalUrl,
        public string $robots,
    ) {}
}
