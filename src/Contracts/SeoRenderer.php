<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Contracts;

use Illuminate\Support\HtmlString;
use IvanBaric\Seo\Data\SeoData;

interface SeoRenderer
{
    public function render(SeoData $data): HtmlString;
}
