<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use IvanBaric\Seo\Models\SeoMeta;
use IvanBaric\Seo\Support\SeoContext;
use IvanBaric\Seo\Support\SeoUniqueKey;

final class SeoMetaRepository
{
    public function __construct(private readonly SeoContext $context) {}

    public function queryFor(Model $model, ?string $locale = null): Builder
    {
        /** @var class-string<SeoMeta> $metaClass */
        $metaClass = config('seo.models.seo_meta', SeoMeta::class);
        $localeKey = $this->context->localeKey($locale);

        return $metaClass::query()
            ->where('seoable_type', $model->getMorphClass())
            ->where('seoable_id', $model->getKey())
            ->where($this->tenantTypeColumn(), $this->context->currentTenantType())
            ->where($this->tenantIdColumn(), $this->context->currentTenantId())
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
        $meta = $this->findFor($model, $locale);

        if ($meta instanceof SeoMeta) {
            return $meta;
        }

        /** @var class-string<SeoMeta> $metaClass */
        $metaClass = config('seo.models.seo_meta', SeoMeta::class);

        $meta = new $metaClass([
            'unique_key' => SeoUniqueKey::make($model, $this->context, $locale),
            $this->tenantTypeColumn() => $this->context->currentTenantType(),
            $this->tenantIdColumn() => $this->context->currentTenantId(),
            $this->tenantUuidColumn() => $this->context->currentTenantUuid(),
            'seoable_type' => $model->getMorphClass(),
            'seoable_id' => $model->getKey(),
            'seoable_uuid' => $this->modelUuid($model),
            'locale' => $this->context->localeKey($locale),
        ]);

        $meta->seoable()->associate($model);
        $meta->save();

        return $meta;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Model $model, array $data, ?string $locale = null): SeoMeta
    {
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

    private function modelUuid(Model $model): ?string
    {
        foreach (['uuid', 'ulid'] as $column) {
            if (isset($model->{$column}) && is_string($model->{$column})) {
                return $model->{$column};
            }
        }

        return null;
    }

    private function tenantTypeColumn(): string
    {
        return (string) config('seo.tenant.type_column', 'tenant_type');
    }

    private function tenantIdColumn(): string
    {
        return (string) config('seo.tenant.id_column', 'team_id');
    }

    private function tenantUuidColumn(): string
    {
        return (string) config('seo.tenant.uuid_column', 'tenant_uuid');
    }
}
