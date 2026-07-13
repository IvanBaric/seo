<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

use IvanBaric\Seo\Models\SeoMeta;

final class SeoModels
{
    /** @return class-string<SeoMeta> */
    public static function meta(): string
    {
        return SeoConfigResolver::metaModel();
    }

    public static function table(): string
    {
        return SeoConfigResolver::metaTable();
    }
}
