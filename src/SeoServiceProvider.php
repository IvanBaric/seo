<?php

declare(strict_types=1);

namespace IvanBaric\Seo;

use Illuminate\Support\ServiceProvider;
use IvanBaric\Seo\Console\ClearSeoCacheCommand;
use IvanBaric\Seo\Console\GenerateSitemapCommand;
use IvanBaric\Seo\Console\InstallSeoCommand;
use IvanBaric\Seo\Contracts\SeoImageResolver;
use IvanBaric\Seo\Contracts\SeoRenderer;
use IvanBaric\Seo\Contracts\SeoSchemaBuilder;
use IvanBaric\Seo\Contracts\SeoUrlResolver;
use IvanBaric\Seo\Services\SeoManager;
use IvanBaric\Seo\Services\SeoMetaRepository;

final class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/seo.php', 'seo');

        $this->app->singleton(SeoMetaRepository::class);
        $this->app->singleton(SeoManager::class);

        $this->app->bind(SeoRenderer::class, fn ($app): SeoRenderer => $app->make(
            $app['config']->get('seo.renderer.class')
        ));

        $this->app->bind(SeoUrlResolver::class, fn ($app): SeoUrlResolver => $app->make(
            $app['config']->get('seo.canonical.resolver')
        ));

        $this->app->bind(SeoImageResolver::class, fn ($app): SeoImageResolver => $app->make(
            $app['config']->get('seo.images.resolver')
        ));

        $this->app->bind(SeoSchemaBuilder::class, fn ($app): SeoSchemaBuilder => $app->make(
            $app['config']->get('seo.schema.default_builder')
        ));

        $this->app->alias(SeoManager::class, 'seo');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/seo.php' => config_path('seo.php'),
        ], 'seo-config');

        $this->publishes([
            __DIR__.'/../database/migrations/create_seo_meta_table.php.stub' => database_path('migrations/'.date('Y_m_d_His').'_create_seo_meta_table.php'),
        ], 'seo-migrations');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/seo'),
        ], 'seo-views');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'seo');

        if ((bool) config('seo.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallSeoCommand::class,
                GenerateSitemapCommand::class,
                ClearSeoCacheCommand::class,
            ]);
        }
    }
}
