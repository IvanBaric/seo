# IvanBaric SEO

Reusable SEO infrastructure for Laravel 11, Laravel 12 and Laravel 13 applications.

The package is backend-first, model-agnostic, config-driven, fallback-driven and context-aware through `ivanbaric/corexis`.

## What This Package Is

IvanBaric SEO stores manual SEO overrides in one polymorphic `seo_meta` table and resolves complete SEO data for any Eloquent model that uses:

```php
use IvanBaric\Seo\Concerns\HasSeo;
```

It supports title, description, keywords, canonical URLs, robots directives, Open Graph, Twitter/X cards, Schema.org JSON-LD, hreflang alternates and sitemap XML.

## What This Package Is Not

This is not a CMS, media package, analytics package or text optimization tool. It does not know about application-specific `Post`, `Page`, `Team` or `User` classes, authentication workflows or public visibility rules.

The package includes small optional form helpers and a publishable admin-card Blade view, but it does not impose an admin panel or persistence workflow on your application.

## Requirements

- PHP `^8.2`
- Laravel components `^11.0 || ^12.0 || ^13.0`
- `ivanbaric/corexis` `dev-main as 1.0.0`

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

SEO context and model infrastructure are provided by Corexis:

- `IvanBaric\Corexis\Contracts\TenantResolver`
- `IvanBaric\Corexis\Contracts\LocaleResolver`
- `IvanBaric\Corexis\Concerns\BelongsToTenant`
- `IvanBaric\Corexis\Concerns\HasUuid`

The SEO package does not implement its own tenant or locale resolver. `SeoMeta` uses the Corexis tenant global scope and UUID route-key behavior, while `SeoContext` only adapts the central Corexis resolvers for locale and cache context.

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

New code that needs operation outcomes should call the Action layer directly:

```php
use IvanBaric\Seo\Actions\UpdateSeoMetaAction;

$result = app(UpdateSeoMetaAction::class)->handle(
    model: $product,
    data: ['title' => 'Manual SEO title'],
    locale: 'en',
);
```

`updateSeo()` remains available as a backward-compatible model helper and delegates to `UpdateSeoMetaAction` internally.

## Architecture

SEO follows the shared IvanBaric write flow for state-changing operations:

```text
Component/Controller/Command -> Action -> Corexis ActionResult -> Domain Event -> Listener
```

Available Actions:

- `UpdateSeoMetaAction`
- `DeleteSeoMetaAction`
- `GenerateSitemapAction`
- `RefreshSeoCacheAction`

Available domain events:

- `SeoMetaUpdated`
- `SeoMetaDeleted`
- `SitemapGenerated`
- `SeoCacheRefreshed`

The package remains model-agnostic. Actions operate on generic Eloquent models or package services and do not know about pages, posts, products, teams, billing, gallery, audit, or application-specific content workflows.

Configured model overrides are resolved through `SeoModels`. A custom `seo.models.seo_meta` class must extend `IvanBaric\Seo\Models\SeoMeta`; invalid configuration fails immediately instead of producing a later relation or query error.

## Form Automation

`SeoFormDefaults` resolves a safe SEO form state that you can use from a Livewire component, controller or form object. It keeps repeated rules out of applications:

- auto title from a form field, model defaults or configured fallback attributes
- auto description from a form field, model defaults or configured fallback attributes
- canonical URL through the configured URL resolver or an explicit form value
- robots from an explicit indexed flag or `shouldBeIndexed()`
- manually edited title and description can be preserved

```php
use IvanBaric\Seo\Data\SeoFormState;
use IvanBaric\Seo\Support\SeoFormDefaults;

private function seoState(): SeoFormState
{
    return SeoFormDefaults::for($this->product)
        ->titleFrom($this->title)
        ->descriptionFrom($this->description)
        ->manualTitle($this->seo_title, $this->seoTitleManuallyEdited)
        ->manualDescription($this->seo_description, $this->seoDescriptionManuallyEdited)
        ->canonicalFrom(route('products.show', $this->product))
        ->indexed($this->product?->status === 'published')
        ->titleMaxLength(255)
        ->descriptionMaxLength(500)
        ->resolve();
}
```

You still decide what "published" means. For most models, implement `shouldBeIndexed()`:

```php
public function shouldBeIndexed(): bool
{
    return $this->status === 'published';
}
```

If you do not pass `indexed()`, `SeoFormDefaults` uses `shouldBeIndexed()` when the model has it, otherwise it assumes the record is indexable.

## Admin Card View

The package ships a small optional Blade card for common SEO form fields. The packaged view uses `ivanbaric/admin-ui` for the panel shell and Flux for form controls:

```blade
<x-seo::admin-card />
```

It binds to these Livewire properties by default:

- `seo_title`
- `seo_description`
- `seo_canonical_url`
- `seo_robots`

Override property names when needed:

```blade
<x-seo::admin-card
    title-model="meta.title"
    description-model="meta.description"
    canonical-model="meta.canonical_url"
    robots-model="meta.robots"
/>
```

The card is intentionally only a view. Publish it when an application needs different wording or layout:

```bash
php artisan vendor:publish --tag=seo-views
```

Because this view uses `admin-ui` and Flux components, the consuming application must have those UI packages installed or publish the view and replace the markup with its own UI components.

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

When Corexis tenancy is enabled, `SeoMeta` receives the current tenant id and is automatically scoped by the Corexis `BelongsToTenant` trait. The tenant column comes from `corexis.tenancy.id_column`; SEO does not define a second tenant resolver or tenant-column setting.

SEO write Actions re-resolve the subject from the database, verify that it belongs to the current tenant and then authorize the operation. A model instance from another tenant cannot be used to update or delete metadata. Existing installations automatically remove the legacy `tenant_type`, `tenant_uuid` and `seoable_uuid` columns while preserving metadata rows.

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

Configured models are included only when a URL can be resolved. If the model
has a `published()` scope, the sitemap query uses it. If the model implements
`shouldBeIndexed()`, hidden records are skipped at generation time.

For CMS applications, prefer custom sitemap sources when public visibility is
application-specific. A source can return Eloquent models or ready-made
`SitemapUrlData` records:

```php
use Illuminate\Support\Collection;
use IvanBaric\Seo\Contracts\SitemapSource;
use IvanBaric\Seo\Data\SitemapUrlData;

final class PublicPageSitemapSource implements SitemapSource
{
    public function sitemapModels(): Collection
    {
        return Page::query()
            ->where('is_active', true)
            ->where('team_is_public', true)
            ->get();
    }
}
```

```php
'sitemap' => [
    'sources' => [
        PublicPageSitemapSource::class,
    ],
],
```

The source owns the business rule. For example:

- pages that only have `is_active` should include only `is_active = true`
- posts with scheduled publishing should include only `status = published` and `published_at <= now()`
- products should include only visible public products
- galleries should include only public galleries with at least one public media item
- taxonomy URLs should be included only when they have at least one public record

This keeps the SEO package reusable while each application decides what "public"
means.

Generate:

```bash
php artisan seo:generate-sitemap --fresh
php artisan seo:generate-sitemap --write=public/sitemap.xml
```

`--write` accepts only relative paths inside `seo.sitemap.write_directory`, which defaults to `public`. Absolute paths and path traversal are rejected. Change the configured directory when deployment requires a different writable target; do not pass an unrestricted filesystem path from a request.

For dynamic sites, expose `/sitemap.xml` through the package route and keep
cache enabled. Clear SEO cache when content visibility or URLs change:

```bash
php artisan seo:clear-cache
```

Typical listeners clear this cache after page, post, product, gallery and
taxonomy create/update/delete/publish/unpublish events.

Sitemap cache keys include the current Corexis tenant, locale and public host. Cached XML from one tenant, locale or domain is therefore never reused for another context. Configured sitemap models should use Corexis tenancy; custom sources remain responsible for application-specific public visibility rules.

## Robots.txt

The SEO package does not force a `robots.txt` file because the right answer is
application-specific. A recommended production policy for admin-backed public
sites is:

```txt
User-agent: *
Allow: /

Disallow: /app/
Disallow: /login
Disallow: /register
Disallow: /forgot-password
Disallow: /reset-password
Disallow: /email/verify
Disallow: /livewire/

Sitemap: https://example.com/sitemap.xml
```

For local, staging and preview environments, prefer:

```txt
User-agent: *
Disallow: /
```

Applications should generate the sitemap URL from their configured public base
URL, not hardcode a development domain.

## Security Notes

Renderer output is escaped. JSON-LD uses `JSON_HEX_TAG`, `JSON_HEX_AMP`, `JSON_HEX_APOS`, `JSON_HEX_QUOT`, `JSON_UNESCAPED_SLASHES` and `JSON_UNESCAPED_UNICODE`. Unsafe URL schemes are neither stored through the Action layer nor rendered.

All state-changing Actions validate and authorize server-side. `UpdateSeoMetaAction` accepts only known SEO fields, validates locale identifiers, URL schemes, robots directives and structured JSON data. Failures are reported with safe Croatian messages; raw exception details are never returned to the caller. `GenerateSitemapAction` constrains filesystem writes to the configured directory.

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
- `sitemap.sources`
- `sitemap.chunk_size`
- `sitemap.cache_enabled`
- `sitemap.cache_key`
- `sitemap.write_directory`
- `renderer.class`
- `cache.enabled`
- `cache.prefix`
- `cache.ttl`
- `routes.enabled`
- `routes.middleware`

## Architecture

Corexis gives tenant and locale context and owns reusable tenant/UUID model behavior. SEO does not know where that context comes from. Models provide SEO defaults, `SeoMeta` stores manual override values, the renderer safely turns `SeoData` into HTML meta tags, and sitemap/hreflang use the same resolver and fallback mechanisms.
