<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Fixtures;

use Illuminate\Support\HtmlString;
use IvanBaric\Seo\Contracts\SeoRenderer;
use IvanBaric\Seo\Data\SeoData;

final class TestRenderer implements SeoRenderer
{
    public function render(SeoData $data): HtmlString
    {
        return new HtmlString('custom:'.$data->title);
    }
}
