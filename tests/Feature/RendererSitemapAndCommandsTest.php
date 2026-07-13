<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use IvanBaric\Corexis\Contracts\TenantResolver;
use IvanBaric\Seo\Actions\GenerateSitemapAction;
use IvanBaric\Seo\Services\SitemapGenerator;
use IvanBaric\Seo\Tests\Fixtures\FixtureSitemapSource;
use IvanBaric\Seo\Tests\Fixtures\Models\SeoFixtureModel;
use IvanBaric\Seo\Tests\Fixtures\Resolvers\MutableTenantResolver;
use IvanBaric\Seo\Tests\TestCase;

final class RendererSitemapAndCommandsTest extends TestCase
{
    public function test_renderer_outputs_safe_meta_tags_and_blocks_unsafe_urls(): void
    {
        $html = seo()->render([
            'title' => '<Unsafe>',
            'description' => 'A description',
            'canonical_url' => 'javascript:alert(1)',
            'og_image' => 'data:text/plain,abc',
            'schema' => ['name' => '<Unsafe>'],
        ])->toHtml();

        $this->assertStringContainsString('<title>&lt;Unsafe&gt;</title>', $html);
        $this->assertStringContainsString('content="A description"', $html);
        $this->assertStringNotContainsString('javascript:alert', $html);
        $this->assertStringNotContainsString('data:text', $html);
        $this->assertStringContainsString('application/ld+json', $html);
        $this->assertStringContainsString('\\u003CUnsafe\\u003E', $html);
    }

    public function test_sitemap_route_and_generator_include_configured_models(): void
    {
        Route::get('/fixtures/{seo_fixture_model}', fn () => 'ok')->name('fixtures.show');

        $model = SeoFixtureModel::query()->create(['title' => 'Public']);
        SeoFixtureModel::query()->create(['title' => 'Hidden', 'indexed' => false]);
        $unpublished = SeoFixtureModel::query()->create(['title' => 'Unpublished', 'published' => false]);

        $xml = app(SitemapGenerator::class)->generate(fresh: true, cache: false);

        $this->assertStringContainsString('https://example.test/fixtures/'.$model->getKey(), $xml);
        $this->assertStringNotContainsString('https://example.test/fixtures/'.$unpublished->getKey(), $xml);
        $this->assertStringNotContainsString('Hidden', $xml);

        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');
    }

    public function test_sitemap_generator_includes_configured_sources(): void
    {
        config()->set('seo.sitemap.sources', [
            FixtureSitemapSource::class,
        ]);

        $xml = app(SitemapGenerator::class)->generate(fresh: true, cache: false);

        $this->assertStringContainsString('https://example.test/custom-source', $xml);
    }

    public function test_commands_are_registered(): void
    {
        $this->assertSame(0, Artisan::call('seo:clear-cache'));
        $this->assertSame(0, Artisan::call('seo:generate-sitemap', ['--fresh' => true, '--no-cache' => true]));
        $this->assertSame(0, Artisan::call('seo:install'));
    }

    public function test_sitemap_cache_key_is_isolated_by_tenant(): void
    {
        $this->app->bind(TenantResolver::class, MutableTenantResolver::class);
        $generator = app(SitemapGenerator::class);

        MutableTenantResolver::$tenantId = 10;
        $tenantTenKey = $generator->cacheKey();

        MutableTenantResolver::$tenantId = 20;
        $tenantTwentyKey = $generator->cacheKey();

        $this->assertNotSame($tenantTenKey, $tenantTwentyKey);
    }

    public function test_cached_sitemap_content_is_not_shared_between_tenants(): void
    {
        Route::get('/fixtures/{seo_fixture_model}', fn () => 'ok')->name('fixtures.show');
        $this->app->bind(TenantResolver::class, MutableTenantResolver::class);
        $generator = app(SitemapGenerator::class);

        MutableTenantResolver::$tenantId = 10;
        $tenantTen = SeoFixtureModel::query()->create(['title' => 'Tenant 10']);
        $tenantTenXml = $generator->generate(fresh: false, cache: true);

        MutableTenantResolver::$tenantId = 20;
        $tenantTwenty = SeoFixtureModel::query()->create(['title' => 'Tenant 20']);
        $tenantTwentyXml = $generator->generate(fresh: false, cache: true);

        $this->assertStringContainsString('/fixtures/'.$tenantTen->getKey(), $tenantTenXml);
        $this->assertStringNotContainsString('/fixtures/'.$tenantTen->getKey(), $tenantTwentyXml);
        $this->assertStringContainsString('/fixtures/'.$tenantTwenty->getKey(), $tenantTwentyXml);
    }

    public function test_sitemap_cannot_be_written_outside_configured_directory(): void
    {
        $result = app(GenerateSitemapAction::class)->handle(
            fresh: true,
            cache: false,
            writePath: '../outside.xml',
        );

        $this->assertTrue($result->failed());
        $this->assertSame('invalid_sitemap_write_path', $result->code);
    }
}
