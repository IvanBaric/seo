<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Feature;

use IvanBaric\Seo\Contracts\SeoRenderer;
use IvanBaric\Seo\Models\SeoMeta;
use IvanBaric\Seo\Tests\Fixtures\TestRenderer;
use IvanBaric\Seo\Tests\TestCase;

final class ConfigAndModelTest extends TestCase
{
    public function test_config_is_merged_and_custom_renderer_binding_works(): void
    {
        $this->assertSame('seo_meta', config('seo.table.name'));

        config()->set('seo.renderer.class', TestRenderer::class);

        $this->assertInstanceOf(TestRenderer::class, app(SeoRenderer::class));
    }

    public function test_seo_meta_model_uses_config_table_casts_and_uuid(): void
    {
        config()->set('seo.table.name', 'seo_meta');

        $meta = SeoMeta::query()->create([
            'unique_key' => str_repeat('a', 64),
            'seoable_type' => 'fixture',
            'seoable_id' => 1,
            'keywords' => ['one', 'two'],
            'schema' => ['@type' => 'WebPage'],
            'metadata' => ['source' => 'manual'],
            'robots' => 'index,follow',
        ]);

        $this->assertSame('seo_meta', $meta->getTable());
        $this->assertNotNull($meta->uuid);
        $this->assertSame(['one', 'two'], $meta->keywords);
        $this->assertTrue($meta->isIndexable());
        $this->assertTrue($meta->hasManualValue('title') === false);
    }
}
