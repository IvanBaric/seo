<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use IvanBaric\Seo\Models\SeoMeta;
use IvanBaric\Seo\Support\SeoContext;
use IvanBaric\Seo\Support\SeoModels;
use IvanBaric\Seo\Support\SeoSubjectResolver;
use IvanBaric\Seo\Support\SeoUniqueKey;
use LogicException;

final class SeoMetaRepository
{
    public function __construct(
        private readonly SeoContext $context,
        private readonly SeoSubjectResolver $subjectResolver,
    ) {}

    /** @return Builder<SeoMeta> */
    public function queryFor(Model $model, ?string $locale = null): Builder
    {
        $metaClass = SeoModels::meta();
        $localeKey = $this->context->localeKey($locale);

        return $metaClass::query()
            ->where('seoable_type', $model->getMorphClass())
            ->where('seoable_id', $model->getKey())
            ->where('locale', $localeKey);
    }

    public function findFor(Model $model, ?string $locale = null): ?SeoMeta
    {
        /** @var SeoMeta|null $meta */
        $meta = $this->queryFor($model, $locale)->first();

        return $meta;
    }

    public function getOrCreate(Model $model, ?string $locale = null): SeoMeta
    {
        $model = $this->writableSubject($model);
        $existing = $this->findFor($model, $locale);

        if ($existing instanceof SeoMeta) {
            return $existing;
        }

        $metaClass = SeoModels::meta();
        $uniqueKey = SeoUniqueKey::make($model, $this->context, $locale);

        /** @var SeoMeta $meta */
        $meta = $metaClass::query()->firstOrCreate([
            'unique_key' => $uniqueKey,
        ], [
            'seoable_type' => $model->getMorphClass(),
            'seoable_id' => $model->getKey(),
            'locale' => $this->context->localeKey($locale),
        ]);

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data, ?string $locale = null): SeoMeta
    {
        $model = $this->writableSubject($model);
        $meta = $this->queryFor($model, $locale)
            ->lockForUpdate()
            ->first();

        if (! $meta instanceof SeoMeta) {
            $meta = $this->getOrCreate($model, $locale);
        }

        $meta->fill($data);
        $meta->unique_key = SeoUniqueKey::make($model, $this->context, $locale);
        $meta->save();

        return $meta;
    }

    public function delete(Model $model, ?string $locale = null): ?SeoMeta
    {
        $model = $this->writableSubject($model);
        $meta = $this->queryFor($model, $locale)->lockForUpdate()->first();

        if (! $meta instanceof SeoMeta) {
            return null;
        }

        $meta->delete();

        return $meta;
    }

    private function writableSubject(Model $model): Model
    {
        $resolved = $this->subjectResolver->resolve($model);

        if (! $resolved instanceof Model) {
            throw new LogicException('SEO metadata can only be written for an available persisted model.');
        }

        return $resolved;
    }
}
