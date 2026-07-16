<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Feature;

use InvalidArgumentException;
use IvanBaric\Corexis\Contracts\LocaleResolver;
use IvanBaric\Corexis\Contracts\TenantResolver;
use IvanBaric\Seo\Actions\DeleteSeoMetaAction;
use IvanBaric\Seo\Actions\UpdateSeoMetaAction;
use IvanBaric\Seo\Models\SeoMeta;
use IvanBaric\Seo\Support\OptionalModelAttribute;
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
        $this->assertSame('10', (string) $meta->team_id);
        $this->assertSame('hr', $meta->locale);
        $this->assertCount(1, $model->seoMetas()->get());
    }

    public function test_actions_do_not_modify_a_model_from_another_tenant(): void
    {
        $this->app->bind(TenantResolver::class, FakeTenantResolver::class);

        $model = SeoFixtureModel::query()->create(['title' => 'Tenant 10']);
        $model->updateSeo(['title' => 'Original']);

        $this->app->instance(TenantResolver::class, new class implements TenantResolver
        {
            public function enabled(): bool
            {
                return true;
            }

            public function current(): mixed
            {
                return ['id' => 20];
            }

            public function id(): int
            {
                return 20;
            }

            public function uuid(): ?string
            {
                return null;
            }

            public function type(): string
            {
                return 'team';
            }
        });

        $update = app(UpdateSeoMetaAction::class)->handle($model, ['title' => 'Compromised']);
        $delete = app(DeleteSeoMetaAction::class)->handle($model);

        $this->assertTrue($update->failed());
        $this->assertSame('seo_model_unavailable', $update->code);
        $this->assertTrue($delete->failed());
        $this->assertSame('seo_model_unavailable', $delete->code);
        $this->assertDatabaseHas('seo_meta', ['title' => 'Original', 'team_id' => '10']);
    }

    public function test_get_or_create_reuses_metadata_with_a_legacy_unique_key(): void
    {
        $this->app->bind(TenantResolver::class, FakeTenantResolver::class);
        $this->app->bind(LocaleResolver::class, FakeLocaleResolver::class);

        $model = SeoFixtureModel::query()->create(['title' => 'Fixture']);
        $legacy = SeoMeta::query()->create([
            'unique_key' => hash('sha256', 'legacy-key'),
            'seoable_type' => $model->getMorphClass(),
            'seoable_id' => $model->getKey(),
            'locale' => 'hr',
            'title' => 'Postojeći naslov',
        ]);

        $resolved = $model->getOrCreateSeoMeta('hr');

        $this->assertTrue($resolved->is($legacy));
        $this->assertSame('Postojeći naslov', $resolved->title);
        $this->assertDatabaseCount('seo_meta', 1);
    }

    public function test_fallback_order_prefers_manual_then_defaults_then_attributes_then_config(): void
    {
        $model = SeoFixtureModel::query()->create(['title' => 'Attribute title']);
        $this->assertSame('Attribute title', $model->seoData()->title);
        $this->assertSame('Default fixture description', $model->seoData()->description);

        $model->updateSeo(['title' => 'Manual title']);

        $this->assertSame('Manual title', $model->seoData()->title);
        $this->assertSame('https://example.test/fixtures/'.$model->getKey(), $model->seoData()->canonicalUrl);
        $this->assertSame('https://example.test/default-fixture.jpg', $model->seoData()->ogImage);
        $this->assertSame('Article', $model->seoData()->schema['@type']);
    }

    public function test_optional_fallback_attributes_skip_columns_that_were_not_selected(): void
    {
        SeoFixtureModel::query()->create(['title' => 'Partial model']);
        $model = SeoFixtureModel::query()->select(['id'])->firstOrFail();

        $this->assertNull(OptionalModelAttribute::get($model, 'image_url'));
        $this->assertNull(OptionalModelAttribute::get($model, 'missing_attribute'));
    }

    public function test_noindex_model_gets_noindex_robots_without_manual_override(): void
    {
        $model = SeoFixtureModel::query()->create(['title' => 'Hidden', 'indexed' => false]);

        $this->assertSame('noindex,nofollow', $model->seoData()->robots);

        $model->updateSeo(['robots' => 'index,follow']);

        $this->assertSame('index,follow', $model->seoData()->robots);
    }

    public function test_direct_repository_helper_rejects_invalid_locale_identifiers(): void
    {
        $model = SeoFixtureModel::query()->create(['title' => 'Fixture']);

        $this->expectException(InvalidArgumentException::class);

        $model->getOrCreateSeoMeta('../../hr');
    }
}
