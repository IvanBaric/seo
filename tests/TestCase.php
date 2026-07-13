<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use IvanBaric\Corexis\CorexisServiceProvider;
use IvanBaric\Seo\SeoServiceProvider;
use IvanBaric\Seo\Tests\Fixtures\Models\SeoFixtureModel;
use IvanBaric\Seo\Tests\Fixtures\Resolvers\MutableTenantResolver;
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
        $app['config']->set('corexis.tenancy.enabled', false);
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

        URL::forceRootUrl('https://example.test');
        URL::forceScheme('https');

        MutableTenantResolver::$tenantId = 10;
        Model::clearBootedModels();
        $this->createSchema();
        Artisan::call('migrate', ['--force' => true]);
    }

    private function createSchema(): void
    {
        Schema::dropIfExists('seo_fixture_models');

        Schema::create('seo_fixture_models', function (Blueprint $table): void {
            $table->id();
            $table->string('team_id')->nullable()->index();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->boolean('indexed')->default(true);
            $table->boolean('published')->default(true);
            $table->timestamps();
        });

    }
}
