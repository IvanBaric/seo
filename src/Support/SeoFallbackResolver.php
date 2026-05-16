<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Seo\Contracts\SeoImageResolver;
use IvanBaric\Seo\Contracts\SeoSchemaBuilder;
use IvanBaric\Seo\Contracts\SeoUrlResolver;
use IvanBaric\Seo\Data\SeoData;
use IvanBaric\Seo\Models\SeoMeta;

final class SeoFallbackResolver
{
    public function __construct(
        private readonly SeoValueNormalizer $normalizer,
        private readonly SeoUrlResolver $urlResolver,
        private readonly SeoImageResolver $imageResolver,
        private readonly SeoSchemaBuilder $schemaBuilder,
    ) {}

    public function resolve(Model $model, ?SeoMeta $meta = null, ?string $locale = null): SeoData
    {
        $defaults = method_exists($model, 'seoDefaults') ? $model->seoDefaults() : [];
        $title = $this->title($model, $meta, $defaults);
        $description = $this->description($model, $meta, $defaults);
        $canonical = $this->normalizer->url(
            $this->manual($meta, 'canonical_url')
            ?? ($defaults['canonical_url'] ?? $defaults['canonical'] ?? null)
            ?? $this->urlResolver->resolve($model, $locale)
            ?? $this->attribute($model, config('seo.fallbacks.canonical', []))
            ?? config('seo.defaults.canonical')
            ?? $this->requestUrl()
        );
        $image = $this->normalizer->url($this->manual($meta, 'og_image') ?? ($defaults['image'] ?? null) ?? $this->imageResolver->resolve($model, $locale));
        $robots = $this->robots($model, $meta, $defaults);

        $data = new SeoData(
            title: $title,
            description: $description,
            keywords: $this->normalizer->keywords($this->manual($meta, 'keywords') ?? ($defaults['keywords'] ?? [])),
            canonicalUrl: $canonical,
            robots: $robots,
            ogTitle: $this->normalizer->string($this->manual($meta, 'og_title') ?? $title),
            ogDescription: $this->normalizer->string($this->manual($meta, 'og_description') ?? $description),
            ogImage: $image,
            ogType: (string) ($this->manual($meta, 'og_type') ?? $defaults['og_type'] ?? config('seo.defaults.og_type', 'website')),
            twitterTitle: $this->normalizer->string($this->manual($meta, 'twitter_title') ?? $this->manual($meta, 'og_title') ?? $title),
            twitterDescription: $this->normalizer->string($this->manual($meta, 'twitter_description') ?? $this->manual($meta, 'og_description') ?? $description),
            twitterImage: $this->normalizer->url($this->manual($meta, 'twitter_image') ?? $image),
            twitterCard: (string) ($this->manual($meta, 'twitter_card') ?? $defaults['twitter_card'] ?? config('seo.defaults.twitter_card', 'summary_large_image')),
            schema: is_array($this->manual($meta, 'schema')) ? $this->manual($meta, 'schema') : $this->schemaBuilder->build($model),
            metadata: is_array($this->manual($meta, 'metadata')) ? $this->manual($meta, 'metadata') : [],
        );

        if ($data->schema === []) {
            return $data;
        }

        return SeoData::fromArray(array_replace($data->toArray(), [
            'schema' => array_replace($this->schemaBuilder->build($data), $data->schema),
        ]));
    }

    /**
     * @param  array<string, mixed>  $defaults
     */
    private function title(Model $model, ?SeoMeta $meta, array $defaults): ?string
    {
        return $this->normalizer->string(
            $this->manual($meta, 'title')
            ?? ($defaults['title'] ?? null)
            ?? $this->attribute($model, config('seo.fallbacks.title', []))
            ?? config('seo.defaults.title')
            ?? config('seo.defaults.site_name'),
            (int) config('seo.limits.title_max_length', 70)
        );
    }

    /**
     * @param  array<string, mixed>  $defaults
     */
    private function description(Model $model, ?SeoMeta $meta, array $defaults): ?string
    {
        return $this->normalizer->string(
            $this->manual($meta, 'description')
            ?? ($defaults['description'] ?? null)
            ?? $this->attribute($model, config('seo.fallbacks.description', []))
            ?? config('seo.defaults.description'),
            (int) config('seo.limits.description_max_length', 160)
        );
    }

    /**
     * @param  array<string, mixed>  $defaults
     */
    private function robots(Model $model, ?SeoMeta $meta, array $defaults): string
    {
        $shouldBeIndexed = ! method_exists($model, 'shouldBeIndexed') || $model->shouldBeIndexed();

        if (! (bool) config('seo.robots.allow_manual_index_override', true) && method_exists($model, 'shouldBeIndexed')) {
            return $shouldBeIndexed
                ? $this->normalizer->robots($defaults['robots'] ?? config('seo.defaults.robots', 'index,follow'))
                : 'noindex,nofollow';
        }

        if ($meta?->hasManualValue('robots')) {
            return $this->normalizer->robots($meta->robots);
        }

        if (! $shouldBeIndexed) {
            return 'noindex,nofollow';
        }

        return $this->normalizer->robots($defaults['robots'] ?? config('seo.defaults.robots', 'index,follow'));
    }

    private function manual(?SeoMeta $meta, string $field): mixed
    {
        return $meta?->hasManualValue($field) ? $meta->{$field} : null;
    }

    /**
     * @param  array<int, string>  $attributes
     */
    private function attribute(Model $model, array $attributes): mixed
    {
        foreach ($attributes as $attribute) {
            $value = $model->getAttribute($attribute);

            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function requestUrl(): ?string
    {
        if (app()->runningInConsole() || ! (bool) config('seo.canonical.request_fallback', true)) {
            return null;
        }

        return request()->fullUrl();
    }
}
