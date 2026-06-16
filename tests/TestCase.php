<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Corexis\CorexisServiceProvider;
use IvanBaric\Seo\SeoServiceProvider;
use IvanBaric\Seo\Tests\Fixtures\Models\SeoFixtureModel;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            CorexisServiceProvider::class,
            SeoServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.url', 'https://example.test');
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('corexis.locale.enabled', true);
        $app['config']->set('corexis.locale.default', 'en');
        $app['config']->set('corexis.locale.fallback', 'en');
        $app['config']->set('corexis.locale.available', ['en', 'hr']);
        $app['config']->set('seo.sitemap.models', [
            SeoFixtureModel::class => ['route' => 'fixtures.show', 'route_key' => 'seo_fixture_model'],
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        SeoFixtureModel::clearBootedModels();
        $this->createSchema();
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('seo_meta');
        Schema::dropIfExists('seo_fixture_models');

        Schema::create('seo_fixture_models', function (Blueprint $table): void {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('indexed')->default(true);
            $table->timestamps();
        });

        Schema::create('seo_meta', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('unique_key', 64)->unique();
            $table->string('tenant_type')->nullable()->index();
            $table->string('team_id')->nullable()->index();
            $table->uuid('tenant_uuid')->nullable()->index();
            $table->string('seoable_type')->index();
            $table->unsignedBigInteger('seoable_id')->index();
            $table->uuid('seoable_uuid')->nullable()->index();
            $table->string('locale')->nullable()->index();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('keywords')->nullable();
            $table->text('canonical_url')->nullable();
            $table->string('robots')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->text('og_image')->nullable();
            $table->string('og_type')->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->text('twitter_image')->nullable();
            $table->string('twitter_card')->nullable();
            $table->json('schema')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['seoable_type', 'seoable_id']);
            $table->index(['tenant_type', 'team_id']);
            $table->index('updated_at');
        });
    }
}
