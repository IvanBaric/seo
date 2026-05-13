<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

use Illuminate\Database\Eloquent\Model;

final class SeoUniqueKey
{
    public static function make(Model $model, SeoContext $context, ?string $locale = null): string
    {
        $tenantType = $context->currentTenantType() ?: 'none';
        $tenantId = $context->currentTenantId();
        $localeKey = $context->localeKey($locale);

        return hash('sha256', implode('|', [
            $tenantType,
            $tenantId === null || $tenantId === '' ? 'none' : (string) $tenantId,
            $model->getMorphClass(),
            (string) $model->getKey(),
            $localeKey,
        ]));
    }
}
