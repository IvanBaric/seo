<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Seo\Contracts\SeoRenderer;
use IvanBaric\Seo\Models\SeoMeta;
use IvanBaric\Seo\Support\SeoModels;
use IvanBaric\Seo\Tests\Fixtures\TestRenderer;
use IvanBaric\Seo\Tests\TestCase;
use LogicException;

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
        $this->assertMatchesRegularExpression('/^[0-9a-f-]{36}$/', $meta->uuid);
        $this->assertSame(['one', 'two'], $meta->keywords);
        $this->assertTrue($meta->isIndexable());
        $this->assertTrue($meta->hasManualValue('title') === false);
    }

    public function test_package_migration_uses_corexis_columns_without_legacy_context_columns(): void
    {
        $columns = Schema::getColumnListing('seo_meta');

        $this->assertContains('team_id', $columns);
        $this->assertNotContains('tenant_type', $columns);
        $this->assertNotContains('tenant_uuid', $columns);
        $this->assertNotContains('seoable_uuid', $columns);
    }

    public function test_invalid_meta_model_override_fails_early(): void
    {
        config()->set('seo.models.seo_meta', \stdClass::class);

        $this->expectException(LogicException::class);

        SeoModels::meta();
    }

    public function test_legacy_context_cleanup_preserves_existing_metadata(): void
    {
        Schema::table('seo_meta', function (Blueprint $table): void {
            $table->string('tenant_type')->nullable();
            $table->uuid('tenant_uuid')->nullable();
            $table->uuid('seoable_uuid')->nullable();
        });

        Schema::table('seo_meta', function (Blueprint $table): void {
            $table->index('tenant_type');
            $table->index('tenant_uuid');
            $table->index('seoable_uuid');
            $table->index(['tenant_type', 'team_id'], 'legacy_seo_tenant_context_index');
        });

        DB::table('seo_meta')->insert([
            'uuid' => '019f5b0b-b987-70a2-baf6-9d8682088abc',
            'unique_key' => str_repeat('b', 64),
            'team_id' => '10',
            'tenant_type' => 'team',
            'tenant_uuid' => '019f5b0b-b987-70a2-baf6-9d8682088abd',
            'seoable_type' => 'fixture',
            'seoable_id' => 99,
            'seoable_uuid' => '019f5b0b-b987-70a2-baf6-9d8682088abe',
            'locale' => '__default',
            'title' => 'Sačuvani SEO zapis',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = require dirname(__DIR__, 2).'/database/migrations/2026_07_13_010000_remove_legacy_seo_tenant_columns.php';
        $migration->up();

        $this->assertDatabaseHas('seo_meta', ['title' => 'Sačuvani SEO zapis', 'team_id' => '10']);
        $this->assertNotContains('tenant_type', Schema::getColumnListing('seo_meta'));
        $this->assertNotContains('tenant_uuid', Schema::getColumnListing('seo_meta'));
        $this->assertNotContains('seoable_uuid', Schema::getColumnListing('seo_meta'));

        $migration->down();
        $this->assertContains('tenant_type', Schema::getColumnListing('seo_meta'));
        $this->assertContains('tenant_uuid', Schema::getColumnListing('seo_meta'));
        $this->assertContains('seoable_uuid', Schema::getColumnListing('seo_meta'));

        $migration->up();
        $this->assertDatabaseHas('seo_meta', ['title' => 'Sačuvani SEO zapis', 'team_id' => '10']);
    }
}
