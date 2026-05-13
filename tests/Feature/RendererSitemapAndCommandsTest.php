<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Feature;

use Illuminate\Support\Facades\Route;
use IvanBaric\Seo\Services\SitemapGenerator;
use IvanBaric\Seo\Tests\Fixtures\Models\SeoFixtureModel;
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

        $xml = app(SitemapGenerator::class)->generate(fresh: true, cache: false);

        $this->assertStringContainsString('https://example.test/fixtures/'.$model->id, $xml);
        $this->assertStringNotContainsString('Hidden', $xml);

        $this->get('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml');
    }

    public function test_commands_are_registered(): void
    {
        $this->artisan('seo:clear-cache')->assertExitCode(0);
        $this->artisan('seo:generate-sitemap', ['--fresh' => true, '--no-cache' => true])->assertExitCode(0);
        $this->artisan('seo:install')->assertExitCode(0);
    }
}
