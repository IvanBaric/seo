<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

use Illuminate\Database\Eloquent\Model;

final class SeoUniqueKey
{
    public static function make(Model $model, SeoContext $context, ?string $locale = null): string
    {
        $tenantId = $context->currentTenantId();
        $localeKey = $context->localeKey($locale);

        return hash('sha256', implode('|', [
            $tenantId === null || $tenantId === '' ? 'none' : (string) $tenantId,
            $model->getMorphClass(),
            (string) $model->getKey(),
            $localeKey,
        ]));
    }
}
