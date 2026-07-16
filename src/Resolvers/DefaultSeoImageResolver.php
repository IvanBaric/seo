<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Resolvers;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Seo\Contracts\SeoImageResolver;
use IvanBaric\Seo\Support\OptionalModelAttribute;
use IvanBaric\Seo\Support\SeoValueNormalizer;

final class DefaultSeoImageResolver implements SeoImageResolver
{
    public function __construct(private readonly SeoValueNormalizer $normalizer) {}

    public function resolve(Model $model, ?string $locale = null): ?string
    {
        if (method_exists($model, 'seoImageUrl')) {
            $url = $this->normalizer->url($model->seoImageUrl());

            if ($url !== null) {
                return $url;
            }
        }

        if (method_exists($model, 'seoDefaults')) {
            $defaults = $model->seoDefaults();
            $url = $this->normalizer->url($defaults['image'] ?? null);

            if ($url !== null) {
                return $url;
            }
        }

        foreach ((array) config('seo.fallbacks.image', []) as $attribute) {
            $url = $this->normalizer->url(OptionalModelAttribute::get($model, (string) $attribute));

            if ($url !== null) {
                return $url;
            }
        }

        return $this->normalizer->url(config('seo.images.default'));
    }
}
