<?php

declare(strict_types=1);

use IvanBaric\Seo\Models\SeoMeta;
use IvanBaric\Seo\Resolvers\DefaultSeoImageResolver;
use IvanBaric\Seo\Resolvers\DefaultSeoSchemaBuilder;
use IvanBaric\Seo\Resolvers\DefaultSeoUrlResolver;
use IvanBaric\Seo\Services\HtmlSeoRenderer;

return [
    'enabled' => true,

    'models' => [
        'seo_meta' => SeoMeta::class,
    ],

    'table' => [
        'name' => 'seo_meta',
        'connection' => null,
    ],

    'uuid' => [
        'enabled' => true,
        'column' => 'uuid',
    ],

    'corexis' => [
        'tenant' => ['mode' => 'inherit'],
        'locale' => ['mode' => 'inherit'],
        'actor' => ['mode' => 'inherit'],
        'source' => ['mode' => 'inherit'],
    ],

    'locale' => [
        'enabled' => true,
        'store_default_locale' => false,
        'fallback_to_default_locale' => true,
        'default_locale_key' => '__default',
    ],

    'defaults' => [
        'site_name' => null,
        'title' => null,
        'description' => null,
        'robots' => 'index,follow',
        'canonical' => null,
        'og_type' => 'website',
        'twitter_card' => 'summary_large_image',
    ],

    'limits' => [
        'title_max_length' => 70,
        'description_max_length' => 160,
    ],

    'fallbacks' => [
        'title' => ['title', 'name', 'label', 'headline'],
        'description' => ['excerpt', 'description', 'summary', 'content'],
        'image' => ['image', 'image_url', 'thumbnail', 'thumbnail_url'],
        'canonical' => ['url', 'slug'],
    ],

    'robots' => [
        'allowed' => [
            'index',
            'noindex',
            'follow',
            'nofollow',
            'noarchive',
            'nosnippet',
            'noimageindex',
            'max-snippet:-1',
            'max-image-preview:large',
            'max-video-preview:-1',
        ],
        'allow_manual_index_override' => true,
    ],

    'open_graph' => [
        'enabled' => true,
    ],

    'twitter' => [
        'enabled' => true,
    ],

    'images' => [
        'resolver' => DefaultSeoImageResolver::class,
        'default' => null,
    ],

    'canonical' => [
        'enabled' => true,
        'resolver' => DefaultSeoUrlResolver::class,
        'absolute' => true,
        'request_fallback' => true,
    ],

    'hreflang' => [
        'enabled' => true,
        'include_x_default' => true,
    ],

    'schema' => [
        'enabled' => true,
        'default_builder' => DefaultSeoSchemaBuilder::class,
        'merge_model_schema' => true,
    ],

    'sitemap' => [
        'enabled' => true,
        'route_enabled' => true,
        'route_path' => 'sitemap.xml',
        'models' => [],
        'chunk_size' => 500,
        'cache_enabled' => true,
        'cache_key' => 'sitemap',
    ],

    'renderer' => [
        'class' => HtmlSeoRenderer::class,
    ],

    'cache' => [
        'enabled' => true,
        'prefix' => 'seo',
        'ttl' => 3600,
    ],

    'routes' => [
        'enabled' => true,
        'middleware' => ['web'],
    ],
];
