<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Corexis\Exceptions\InvalidConfiguration;
use IvanBaric\Corexis\Support\ConfigResolver;
use IvanBaric\Seo\Contracts\SeoImageResolver;
use IvanBaric\Seo\Contracts\SeoRenderer;
use IvanBaric\Seo\Contracts\SeoSchemaBuilder;
use IvanBaric\Seo\Contracts\SeoUrlResolver;
use IvanBaric\Seo\Contracts\SitemapSource;
use IvanBaric\Seo\Models\SeoMeta;
use IvanBaric\Seo\Resolvers\DefaultSeoImageResolver;
use IvanBaric\Seo\Resolvers\DefaultSeoSchemaBuilder;
use IvanBaric\Seo\Resolvers\DefaultSeoUrlResolver;
use IvanBaric\Seo\Services\HtmlSeoRenderer;

final class SeoConfigResolver
{
    /** @return class-string<SeoMeta> */
    public static function metaModel(): string
    {
        return app(ConfigResolver::class)->model(
            key: 'seo.models.seo_meta',
            default: SeoMeta::class,
            expectedType: SeoMeta::class,
        );
    }

    public static function metaTable(): string
    {
        return app(ConfigResolver::class)->table(
            key: 'seo.table.name',
            default: 'seo_meta',
        );
    }

    /** @return class-string<SeoRenderer> */
    public static function renderer(): string
    {
        return app(ConfigResolver::class)->implementation(
            key: 'seo.renderer.class',
            default: HtmlSeoRenderer::class,
            expectedType: SeoRenderer::class,
        );
    }

    /** @return class-string<SeoUrlResolver> */
    public static function urlResolver(): string
    {
        return app(ConfigResolver::class)->implementation(
            key: 'seo.canonical.resolver',
            default: DefaultSeoUrlResolver::class,
            expectedType: SeoUrlResolver::class,
        );
    }

    /** @return class-string<SeoImageResolver> */
    public static function imageResolver(): string
    {
        return app(ConfigResolver::class)->implementation(
            key: 'seo.images.resolver',
            default: DefaultSeoImageResolver::class,
            expectedType: SeoImageResolver::class,
        );
    }

    /** @return class-string<SeoSchemaBuilder> */
    public static function schemaBuilder(): string
    {
        return app(ConfigResolver::class)->implementation(
            key: 'seo.schema.default_builder',
            default: DefaultSeoSchemaBuilder::class,
            expectedType: SeoSchemaBuilder::class,
        );
    }

    /**
     * @return array<class-string<Model>, array<string, mixed>>
     */
    public static function sitemapModels(): array
    {
        $configured = config('seo.sitemap.models', []);

        if (! is_array($configured)) {
            throw InvalidConfiguration::invalidClass(
                key: 'seo.sitemap.models',
                value: $configured,
                expectedType: Model::class,
            );
        }

        $models = [];

        foreach ($configured as $modelClass => $options) {
            if (! is_string($modelClass) || ! class_exists($modelClass) || ! is_a($modelClass, Model::class, true)) {
                throw InvalidConfiguration::invalidClass(
                    key: 'seo.sitemap.models',
                    value: $modelClass,
                    expectedType: Model::class,
                );
            }

            $models[$modelClass] = is_array($options) ? $options : [];
        }

        return $models;
    }

    /**
     * @return array<string, mixed>
     */
    public static function sitemapModelOptions(Model $model): array
    {
        return self::sitemapModels()[$model::class] ?? [];
    }

    /**
     * @return list<class-string<SitemapSource>>
     */
    public static function sitemapSources(): array
    {
        $configured = config('seo.sitemap.sources', []);

        if (! is_array($configured)) {
            throw InvalidConfiguration::invalidClass(
                key: 'seo.sitemap.sources',
                value: $configured,
                expectedType: SitemapSource::class,
            );
        }

        $sources = [];

        foreach ($configured as $index => $source) {
            if ($source === null || $source === '') {
                continue;
            }

            if (! is_string($source)) {
                throw InvalidConfiguration::invalidClass(
                    key: 'seo.sitemap.sources.'.$index,
                    value: $source,
                    expectedType: SitemapSource::class,
                );
            }

            $sources[] = app(ConfigResolver::class)->implementation(
                key: 'seo.sitemap.sources.'.$index,
                default: $source,
                expectedType: SitemapSource::class,
            );
        }

        return $sources;
    }
}
