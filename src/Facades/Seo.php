<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \IvanBaric\Seo\Data\SeoData for(\Illuminate\Database\Eloquent\Model $model, ?string $locale = null)
 * @method static \Illuminate\Support\HtmlString render(\Illuminate\Database\Eloquent\Model|\IvanBaric\Seo\Data\SeoData|array $source, ?string $locale = null)
 * @method static \IvanBaric\Seo\Data\SeoData fromArray(array $data)
 */
final class Seo extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'seo';
    }
}
