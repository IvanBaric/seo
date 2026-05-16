<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Contracts;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Seo\Data\SeoData;

interface SeoSchemaBuilder
{
    /**
     * @param  Model|array<string, mixed>|SeoData  $source
     * @return array<string, mixed>
     */
    public function build(Model|array|SeoData $source): array;
}
