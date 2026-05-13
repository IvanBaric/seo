<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Contracts;

use Illuminate\Database\Eloquent\Model;

interface SeoSchemaBuilder
{
    /**
     * @param  Model|array<string, mixed>|\IvanBaric\Seo\Data\SeoData  $source
     * @return array<string, mixed>
     */
    public function build(Model|array|\IvanBaric\Seo\Data\SeoData $source): array;
}
