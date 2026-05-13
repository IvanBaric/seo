<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Feature;

use IvanBaric\Corexis\Contracts\LocaleResolver;
use IvanBaric\Corexis\Contracts\TenantResolver;
use IvanBaric\Seo\Tests\Fixtures\Models\SeoFixtureModel;
use IvanBaric\Seo\Tests\Fixtures\Resolvers\FakeLocaleResolver;
use IvanBaric\Seo\Tests\Fixtures\Resolvers\FakeTenantResolver;
use IvanBaric\Seo\Tests\TestCase;

final class HasSeoAndCorexisTest extends TestCase
{
    public function test_get_or_create_and_update_seo_store_corexis_context(): void
    {
        $this->app->bind(TenantResolver::class, FakeTenantResolver::class);
        $this->app->bind(LocaleResolver::class, FakeLocaleResolver::class);

        $model = SeoFixtureModel::query()->create(['title' => 'Fixture title']);
        $meta = $model->updateSeo(['title' => 'Manual title']);

        $this->assertSame('Manual title', $model->seoMeta()?->title);
        $this->assertSame('team', $meta->tenant_type);
        $this->assertSame('10', (string) $meta->tenant_id);
        $this->assertSame('hr', $meta->locale);
        $this->assertCount(1, $model->seoMetas()->get());
    }

    public function test_fallback_order_prefers_manual_then_defaults_then_attributes_then_config(): void
    {
        $model = SeoFixtureModel::query()->create(['title' => 'Attribute title']);
        $this->assertSame('Attribute title', $model->seoData()->title);
        $this->assertSame('Default fixture description', $model->seoData()->description);

        $model->updateSeo(['title' => 'Manual title']);

        $this->assertSame('Manual title', $model->seoData()->title);
        $this->assertSame('https://example.test/fixtures/'.$model->id, $model->seoData()->canonicalUrl);
        $this->assertSame('https://example.test/default-fixture.jpg', $model->seoData()->ogImage);
        $this->assertSame('Article', $model->seoData()->schema['@type']);
    }

    public function test_noindex_model_gets_noindex_robots_without_manual_override(): void
    {
        $model = SeoFixtureModel::query()->create(['title' => 'Hidden', 'indexed' => false]);

        $this->assertSame('noindex,nofollow', $model->seoData()->robots);

        $model->updateSeo(['robots' => 'index,follow']);

        $this->assertSame('index,follow', $model->seoData()->robots);
    }
}
