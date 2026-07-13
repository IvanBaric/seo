<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Services;

use DateTimeInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use IvanBaric\Seo\Contracts\SeoUrlResolver;
use IvanBaric\Seo\Data\SitemapUrlData;
use IvanBaric\Seo\Support\SeoConfigResolver;
use IvanBaric\Seo\Support\SeoContext;
use IvanBaric\Seo\Support\SeoValueNormalizer;

final class SitemapGenerator
{
    public function __construct(
        private readonly SeoUrlResolver $urlResolver,
        private readonly HreflangGenerator $hreflangGenerator,
        private readonly SeoValueNormalizer $normalizer,
        private readonly CacheRepository $cache,
        private readonly SeoContext $context,
    ) {}

    public function generate(bool $fresh = false, bool $cache = true): string
    {
        $cacheKey = $this->cacheKey();

        if ($cache && ! $fresh && $this->cacheEnabled()) {
            return (string) $this->cache->remember($cacheKey, $this->cacheTtl(), fn (): string => $this->build());
        }

        $xml = $this->build();

        if ($cache && $this->cacheEnabled()) {
            $this->cache->put($cacheKey, $xml, $this->cacheTtl());
        }

        return $xml;
    }

    private function build(): string
    {
        $urls = [];

        foreach (SeoConfigResolver::sitemapModels() as $modelClass => $options) {
            /** @var Builder<Model> $query */
            $query = $modelClass::query();

            if ($query->hasNamedScope('published')) {
                $query->scopes('published');
            }

            $chunkSize = max(1, min(5000, (int) ($options['chunk_size'] ?? config('seo.sitemap.chunk_size', 500))));

            $query->chunk($chunkSize, function ($models) use (&$urls): void {
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

        return '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL
            .'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">'.PHP_EOL
            .implode(PHP_EOL, $urls).PHP_EOL
            .'</urlset>';
    }

    private function urlNodeFromModel(Model $model, string $url): string
    {
        $lines = ['  <url>', '    <loc>'.$this->e($url).'</loc>'];

        $updatedAt = $model->getAttribute('updated_at');

        if ($updatedAt instanceof DateTimeInterface) {
            $lines[] = '    <lastmod>'.$this->e($updatedAt->format(DATE_ATOM)).'</lastmod>';
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
        $entries = [];

        foreach (SeoConfigResolver::sitemapSources() as $sourceClass) {
            $source = app($sourceClass);

            foreach ($source->sitemapModels() as $entry) {
                $entry = $this->validSourceEntry($entry);

                if ($entry instanceof Model || $entry instanceof SitemapUrlData) {
                    $entries[] = $entry;
                }
            }
        }

        return collect($entries);
    }

    private function validSourceEntry(mixed $entry): Model|SitemapUrlData|null
    {
        return $entry instanceof Model || $entry instanceof SitemapUrlData ? $entry : null;
    }

    public function cacheKey(): string
    {
        $context = hash('sha256', json_encode([
            'tenant' => $this->context->currentTenantId(),
            'locale' => $this->context->localeKey(),
            'host' => app()->runningInConsole()
                ? (string) config('app.url')
                : request()->getSchemeAndHttpHost(),
        ], JSON_THROW_ON_ERROR));

        return (string) config('seo.cache.prefix', 'seo')
            .':'.(string) config('seo.sitemap.cache_key', 'sitemap')
            .':'.$context;
    }

    private function cacheEnabled(): bool
    {
        return (bool) config('seo.cache.enabled', true)
            && (bool) config('seo.sitemap.cache_enabled', true);
    }

    private function cacheTtl(): int
    {
        return max(1, (int) config('seo.cache.ttl', 3600));
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}
