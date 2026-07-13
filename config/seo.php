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
        'sources' => [],
        'chunk_size' => 500,
        'cache_enabled' => true,
        'cache_key' => 'sitemap',
        'write_directory' => 'public',
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

    'permissions' => [
        [
            'name' => 'seo',
            'slug' => 'seo',
            'label' => 'seo::permissions.group',
            'description' => 'seo::permissions.description',
            'icon' => 'search',
            'sort_order' => 80,
            'items' => [
                ['name' => 'View', 'slug' => 'view', 'code' => 'seo.view', 'label' => 'seo::permissions.view', 'sort_order' => 10],
                ['name' => 'Update metadata', 'slug' => 'meta_update', 'code' => 'seo.meta.update', 'label' => 'seo::permissions.meta_update', 'sort_order' => 20],
                ['name' => 'Delete metadata', 'slug' => 'meta_delete', 'code' => 'seo.meta.delete', 'label' => 'seo::permissions.meta_delete', 'sort_order' => 30],
                ['name' => 'Generate sitemap', 'slug' => 'sitemap_generate', 'code' => 'seo.sitemap.generate', 'label' => 'seo::permissions.sitemap_generate', 'sort_order' => 40],
            ],
        ],
    ],
];
