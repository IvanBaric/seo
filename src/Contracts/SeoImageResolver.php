<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Contracts;

use Illuminate\Database\Eloquent\Model;

interface SeoImageResolver
{
    public function resolve(Model $model, ?string $locale = null): ?string;
}
