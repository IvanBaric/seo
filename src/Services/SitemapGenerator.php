<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use IvanBaric\Seo\Contracts\SeoUrlResolver;
use IvanBaric\Seo\Contracts\SitemapSource;
use IvanBaric\Seo\Data\SitemapUrlData;
use IvanBaric\Seo\Support\SeoValueNormalizer;

final class SitemapGenerator
{
    public function __construct(
        private readonly SeoUrlResolver $urlResolver,
        private readonly HreflangGenerator $hreflangGenerator,
        private readonly SeoValueNormalizer $normalizer,
        private readonly CacheRepository $cache,
    ) {}

    public function generate(bool $fresh = false, bool $cache = true): string
    {
        $cacheKey = $this->cacheKey();

        if ($cache && ! $fresh && (bool) config('seo.sitemap.cache_enabled', true)) {
            return (string) $this->cache->remember($cacheKey, (int) config('seo.cache.ttl', 3600), fn (): string => $this->build());
        }

        $xml = $this->build();

        if ($cache && (bool) config('seo.sitemap.cache_enabled', true)) {
            $this->cache->put($cacheKey, $xml, (int) config('seo.cache.ttl', 3600));
        }

        return $xml;
    }

    private function build(): string
    {
        $urls = [];

        foreach ((array) config('seo.sitemap.models', []) as $modelClass => $options) {
            if (! is_string($modelClass) || ! is_subclass_of($modelClass, Model::class)) {
                continue;
            }

            /** @var Builder $query */
            $query = $modelClass::query();

            if (method_exists($modelClass, 'scopePublished')) {
                $query->published();
            }

            $query->chunk((int) ($options['chunk_size'] ?? config('seo.sitemap.chunk_size', 500)), function ($models) use (&$urls): void {
                foreach ($models as $model) {
                    if (method_exists($model, 'shouldBeIndexed') && ! $model->shouldBeIndexed()) {
                        continue;
                    }

                    $url = $this->normalizer->url($this->urlResolver->resolve($model));

                    if ($url === null) {
                        continue;
                    }

                    $urls[$url] = $this->urlNodeFromModel($model, $url);
                }
            });
        }

        foreach ($this->sourceEntries() as $entry) {
            if ($entry instanceof Model) {
                if (method_exists($entry, 'shouldBeIndexed') && ! $entry->shouldBeIndexed()) {
                    continue;
                }

                $url = $this->normalizer->url($this->urlResolver->resolve($entry));

                if ($url === null) {
                    continue;
                }

                $urls[$url] = $this->urlNodeFromModel($entry, $url);

                continue;
            }

            if ($entry instanceof SitemapUrlData) {
                $url = $this->normalizer->url($entry->url);

                if ($url === null) {
                    continue;
                }

                $urls[$url] = $this->urlNodeFromData(new SitemapUrlData(
                    url: $url,
                    lastmod: $entry->lastmod,
                    alternates: $entry->alternates,
                ));
            }
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'.PHP_EOL
            .implode(PHP_EOL, $urls).PHP_EOL
            .'</urlset>';
    }

    private function urlNodeFromModel(Model $model, string $url): string
    {
        $lines = ['  <url>', '    <loc>'.$this->e($url).'</loc>'];

        if ($model->getAttribute('updated_at')) {
            $lines[] = '    <lastmod>'.$this->e($model->getAttribute('updated_at')->toAtomString()).'</lastmod>';
        }

        foreach ($this->hreflangGenerator->for($model) as $alternate) {
            $lines[] = '    <xhtml:link rel="alternate" hreflang="'.$this->e($alternate->locale).'" href="'.$this->e($alternate->url).'" />';
        }

        $lines[] = '  </url>';

        return implode(PHP_EOL, $lines);
    }

    private function urlNodeFromData(SitemapUrlData $data): string
    {
        $lines = ['  <url>', '    <loc>'.$this->e($data->url).'</loc>'];

        if ($data->lastmod) {
            $lines[] = '    <lastmod>'.$this->e($data->lastmod->format(DATE_ATOM)).'</lastmod>';
        }

        foreach ($data->alternates as $alternate) {
            $lines[] = '    <xhtml:link rel="alternate" hreflang="'.$this->e($alternate->locale).'" href="'.$this->e($alternate->url).'" />';
        }

        $lines[] = '  </url>';

        return implode(PHP_EOL, $lines);
    }

    /**
     * @return Collection<int, Model|SitemapUrlData>
     */
    private function sourceEntries(): Collection
    {
        return collect((array) config('seo.sitemap.sources', []))
            ->flatMap(function (mixed $source): iterable {
                if (is_string($source) && class_exists($source)) {
                    $source = app($source);
                }

                if (! $source instanceof SitemapSource) {
                    return [];
                }

                return $source->sitemapModels();
            })
            ->filter(fn (mixed $entry): bool => $entry instanceof Model || $entry instanceof SitemapUrlData)
            ->values();
    }

    private function cacheKey(): string
    {
        return (string) config('seo.cache.prefix', 'seo').':'.(string) config('seo.sitemap.cache_key', 'sitemap');
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}
