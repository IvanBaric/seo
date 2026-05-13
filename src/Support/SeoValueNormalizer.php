<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

use Illuminate\Support\Str;

final class SeoValueNormalizer
{
    public function string(mixed $value, ?int $limit = null): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = Str::squish((string) $value);

        if ($value === '') {
            return null;
        }

        return $limit ? Str::limit($value, $limit, '') : $value;
    }

    /**
     * @return array<int, string>
     */
    public function keywords(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (mixed $keyword): ?string => $this->string($keyword),
            $value
        ), static fn (?string $keyword): bool => $keyword !== null && $keyword !== '')));
    }

    public function robots(mixed $value): string
    {
        return SeoRobots::make((string) ($value ?: config('seo.defaults.robots', 'index,follow')));
    }

    public function url(mixed $value): ?string
    {
        $value = $this->string($value);

        if ($value === null || $this->isUnsafeUrl($value)) {
            return null;
        }

        if (str_starts_with($value, '//')) {
            return null;
        }

        if (str_starts_with($value, '/')) {
            return url($value);
        }

        if (filter_var($value, FILTER_VALIDATE_URL) && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true)) {
            return $value;
        }

        return null;
    }

    public function isUnsafeUrl(string $url): bool
    {
        $scheme = parse_url($url, PHP_URL_SCHEME);

        return in_array(strtolower((string) $scheme), ['javascript', 'data', 'vbscript'], true);
    }
}
