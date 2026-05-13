<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Services;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use IvanBaric\Seo\Contracts\SeoUrlResolver;
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

                    $urls[] = $this->urlNode($model, $url);
                }
            });
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'.PHP_EOL
            .implode(PHP_EOL, $urls).PHP_EOL
            .'</urlset>';
    }

    private function urlNode(Model $model, string $url): string
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

    private function cacheKey(): string
    {
        return (string) config('seo.cache.prefix', 'seo').':'.(string) config('seo.sitemap.cache_key', 'sitemap');
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}
