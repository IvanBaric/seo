<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use IvanBaric\Seo\Actions\UpdateSeoMetaAction;
use IvanBaric\Seo\Data\SeoData;
use IvanBaric\Seo\Models\SeoMeta;
use IvanBaric\Seo\Services\SeoManager;
use IvanBaric\Seo\Services\SeoMetaRepository;
use IvanBaric\Seo\Support\SeoModels;
use RuntimeException;

trait HasSeo
{
    /** @return MorphMany<SeoMeta, $this> */
    public function seoMetas(): MorphMany
    {
        return $this->morphMany(SeoModels::meta(), 'seoable');
    }

    public function seoMeta(?string $locale = null): ?SeoMeta
    {
        return app(SeoMetaRepository::class)->findFor($this, $locale);
    }

    /** @return Builder<SeoMeta> */
    public function seoMetaQuery(?string $locale = null): Builder
    {
        return app(SeoMetaRepository::class)->queryFor($this, $locale);
    }

    public function getOrCreateSeoMeta(?string $locale = null): SeoMeta
    {
        return app(SeoMetaRepository::class)->getOrCreate($this, $locale);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSeo(array $data, ?string $locale = null): SeoMeta
    {
        $result = app(UpdateSeoMetaAction::class)->handle($this, $data, $locale);

        if ($result->failed()) {
            throw new RuntimeException($result->message);
        }

        if (! $result->data instanceof SeoMeta) {
            throw new RuntimeException('SEO meta action did not return a SeoMeta instance.');
        }

        return $result->data;
    }

    public function seoData(?string $locale = null): SeoData
    {
        return app(SeoManager::class)->for($this, $locale);
    }

    /**
     * @return array<string, mixed>
     */
    public function seoDefaults(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function seoSchema(): array
    {
        return [];
    }

    public function seoCanonicalUrl(): ?string
    {
        return null;
    }

    public function seoImageUrl(): ?string
    {
        return null;
    }

    public function shouldBeIndexed(): bool
    {
        return true;
    }
}
