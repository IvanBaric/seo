<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Corexis\Contracts\TenantResolver;

final readonly class SeoSubjectResolver
{
    public function __construct(private TenantResolver $tenantResolver) {}

    public function resolve(Model $model): ?Model
    {
        if (! $model->exists || $model->getKey() === null) {
            return null;
        }

        $resolved = $model->newQuery()->whereKey($model->getKey())->first();

        if (! $resolved instanceof Model || ! $this->belongsToCurrentTenant($resolved)) {
            return null;
        }

        return $resolved;
    }

    private function belongsToCurrentTenant(Model $model): bool
    {
        if (! $this->tenantResolver->enabled()) {
            return true;
        }

        $tenantId = $this->tenantResolver->id();

        if ($tenantId === null) {
            return false;
        }

        $column = method_exists($model, 'getTenantColumn')
            ? $model->getTenantColumn()
            : (string) config('corexis.tenancy.id_column', 'team_id');
        $attributes = $model->getAttributes();

        return ! array_key_exists($column, $attributes)
            || (string) $attributes[$column] === (string) $tenantId;
    }
}
