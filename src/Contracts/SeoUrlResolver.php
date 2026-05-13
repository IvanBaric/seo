<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Contracts;

use Illuminate\Database\Eloquent\Model;

interface SeoUrlResolver
{
    public function resolve(Model $model, ?string $locale = null): ?string;

    public function alternate(Model $model, string $locale): ?string;
}
