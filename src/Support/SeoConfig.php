<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

final class SeoConfig
{
    public function get(string $key, mixed $default = null): mixed
    {
        return config('seo.'.$key, $default);
    }

    public function enabled(): bool
    {
        return (bool) $this->get('enabled', true);
    }
}
