# IvanBaric SEO

Reusable SEO infrastructure for Laravel 11 and Laravel 12 applications.

The package is backend-first, model-agnostic, config-driven, fallback-driven and context-aware through `ivanbaric/corexis`.

## What This Package Is

IvanBaric SEO stores manual SEO overrides in one polymorphic `seo_meta` table and resolves complete SEO data for any Eloquent model that uses:

```php
use IvanBaric\Seo\Concerns\HasSeo;
```

It supports title, description, keywords, canonical URLs, robots directives, Open Graph, Twitter/X cards, Schema.org JSON-LD, hreflang alternates and sitemap XML.

## What This Package Is Not

This is not an admin UI, CMS, Livewire component package, media package, analytics package or text optimization tool. It does not know about `Post`, `Page`, `Team`, `User`, Velora, team ids, app locales or authentication.

## Requirements

- PHP `^8.2`
- Laravel components `^11.0 || ^12.0`
- `ivanbaric/corexis` `^0.1 || ^1.0`

## Installation

```bash
composer require ivanbaric/seo
php artisan seo:install
php artisan migrate
```

Publish manually:

```bash
php artisan vendor:publish --tag=seo-config
php artisan vendor:publish --tag=seo-migrations
php artisan vendor:publish --tag=seo-views
```

## Corexis Dependency

SEO context is read through Corexis contracts:

- `IvanBaric\Corexis\Contracts\TenantResolver`
- `IvanBaric\Corexis\Contracts\LocaleResolver`
- `IvanBaric\Corexis\Contracts\ActorResolver`
- `IvanBaric\Corexis\Contracts\SourceResolver`

The SEO package does not implement its own tenant or locale resolver. `SeoContext` is only a thin adapter over Corexis.

## Basic Usage

```php
use Illuminate\Database\Eloquent\Model;
use IvanBaric\Seo\Concerns\HasSeo;

final class Product extends Model
{
    use HasSeo;

    public function seoDefaults(): array
    {
        return [
            'title' => $this->name,
            'description' => $this->summary,
            'image' => $this->image_url,
        ];
    }

    public function seoCanonicalUrl(): ?string
    {
        return route('products.show', $this);
    }
}
```

## Manual SEO Update

```php
$product->updateSeo([
    'title' => 'Manual SEO title',
    'description' => 'Manual meta description',
    'robots' => 'index,follow',
], locale: 'en');
```

## Rendering Meta Tags

```blade
{!! seo()->render($product) !!}

<x-seo::meta :model="$product" />
<x-seo::meta :data="$seoData" />
```

You can also use dependency injection:

```php
use IvanBaric\Seo\Services\SeoManager;

$seoData = app(SeoManager::class)->for($product);
```

## SEO Fallbacks

Resolution order:

1. Manual value from `seo_meta`
2. Model `seoDefaults()`
3. Model methods such as `seoCanonicalUrl()` and `seoImageUrl()`
4. Model attributes configured in `seo.fallbacks`
5. Global defaults from `seo.defaults`
6. `null`

## Tenant Awareness

When Corexis tenancy is enabled, `SeoMetaRepository` stores `tenant_type`, `tenant_id` and `tenant_uuid` from `TenantResolver`. If tenancy is disabled, those fields remain `null`.

## Locale Awareness

Locale storage uses Corexis `LocaleResolver`. If locale storage is disabled or the current locale is the default and `seo.locale.store_default_locale` is `false`, the package stores `seo.locale.default_locale_key`.

## Canonical URLs

Canonical URLs are resolved by `seo.canonical.resolver`, defaulting to `DefaultSeoUrlResolver`. Unsafe `javascript:` and `data:` URLs are rejected. App-relative URLs are converted to absolute URLs.

## Open Graph And Twitter/X

Open Graph and Twitter/X rendering can be toggled with:

```php
'open_graph.enabled' => true,
'twitter.enabled' => true,
```

The renderer skips null values and escapes HTML attributes.

## Schema.org JSON-LD

Schema rendering is controlled by `seo.schema.enabled`. The default builder emits a `WebPage` schema and merges model `seoSchema()` when `seo.schema.merge_model_schema` is enabled.

## Hreflang

`HreflangGenerator` uses Corexis available locales and `SeoUrlResolver::alternate()`. It does not hardcode locale prefixes.

## Sitemap

Enable sitemap routing:

```php
'sitemap.enabled' => true,
'sitemap.route_enabled' => true,
'sitemap.route_path' => 'sitemap.xml',
```

Configure models:

```php
'sitemap' => [
    'models' => [
        Product::class => [
            'route' => 'products.show',
            'route_key' => 'product',
        ],
    ],
],
```

Generate:

```bash
php artisan seo:generate-sitemap --fresh
php artisan seo:generate-sitemap --write=public/sitemap.xml
```

## Security Notes

Renderer output is escaped. JSON-LD uses `JSON_HEX_TAG`, `JSON_HEX_AMP`, `JSON_HEX_APOS`, `JSON_HEX_QUOT`, `JSON_UNESCAPED_SLASHES` and `JSON_UNESCAPED_UNICODE`. Unsafe URL schemes are not rendered.

## Testing

```bash
composer test
```

The test suite uses Orchestra Testbench and fixture Corexis resolvers.

## Configuration Reference

Main config keys:

- `enabled`
- `models.seo_meta`
- `table.name`
- `table.connection`
- `uuid.enabled`
- `uuid.column`
- `corexis.tenant.mode`
- `corexis.locale.mode`
- `corexis.actor.mode`
- `corexis.source.mode`
- `locale.enabled`
- `locale.store_default_locale`
- `locale.fallback_to_default_locale`
- `locale.default_locale_key`
- `defaults.site_name`
- `defaults.title`
- `defaults.description`
- `defaults.robots`
- `defaults.canonical`
- `defaults.og_type`
- `defaults.twitter_card`
- `limits.title_max_length`
- `limits.description_max_length`
- `fallbacks.title`
- `fallbacks.description`
- `fallbacks.image`
- `fallbacks.canonical`
- `robots.allowed`
- `open_graph.enabled`
- `twitter.enabled`
- `images.resolver`
- `images.default`
- `canonical.enabled`
- `canonical.resolver`
- `hreflang.enabled`
- `schema.enabled`
- `schema.default_builder`
- `sitemap.enabled`
- `sitemap.route_enabled`
- `sitemap.route_path`
- `sitemap.models`
- `renderer.class`
- `cache.enabled`
- `cache.prefix`
- `cache.ttl`
- `routes.enabled`
- `routes.middleware`

## Architecture

Corexis gives tenant, locale, actor and source context. SEO package does not know where that context comes from. Models provide SEO defaults. `SeoMeta` stores manual override values. The renderer safely turns `SeoData` into HTML meta tags. Sitemap and hreflang use the same resolver and fallback mechanisms.
