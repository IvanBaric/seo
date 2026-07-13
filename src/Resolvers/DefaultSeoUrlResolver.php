<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Resolvers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use IvanBaric\Seo\Contracts\SeoUrlResolver;
use IvanBaric\Seo\Support\SeoConfigResolver;
use IvanBaric\Seo\Support\SeoValueNormalizer;

final class DefaultSeoUrlResolver implements SeoUrlResolver
{
    public function __construct(private readonly SeoValueNormalizer $normalizer) {}

    public function resolve(Model $model, ?string $locale = null): ?string
    {
        if (method_exists($model, 'seoCanonicalUrl')) {
            $url = $this->normalizer->url($model->seoCanonicalUrl());

            if ($url !== null) {
                return $url;
            }
        }

        if (method_exists($model, 'seoDefaults')) {
            $defaults = $model->seoDefaults();
            $url = $this->normalizer->url($defaults['canonical_url'] ?? $defaults['canonical'] ?? null);

            if ($url !== null) {
                return $url;
            }
        }

        return $this->routeUrl($model, $locale);
    }

    public function alternate(Model $model, string $locale): ?string
    {
        if (method_exists($model, 'seoAlternateUrl')) {
            return $this->normalizer->url($model->seoAlternateUrl($locale));
        }

        return $this->routeUrl($model, $locale);
    }

    private function routeUrl(Model $model, ?string $locale = null): ?string
    {
        $map = SeoConfigResolver::sitemapModelOptions($model);

        if (! is_array($map) || ! isset($map['route'])) {
            return null;
        }

        $parameter = (string) ($map['route_key'] ?? $model->getRouteKeyName());
        $params = [$parameter => $model->getRouteKey()];

        if ($locale !== null && ($map['locale_parameter'] ?? false)) {
            $params[(string) $map['locale_parameter']] = $locale;
        }

        return Route::has((string) $map['route']) ? route((string) $map['route'], $params) : null;
    }
}
