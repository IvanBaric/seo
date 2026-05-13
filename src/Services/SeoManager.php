<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use IvanBaric\Seo\Contracts\SeoRenderer;
use IvanBaric\Seo\Data\SeoData;
use IvanBaric\Seo\Support\SeoFallbackResolver;

final class SeoManager
{
    public function __construct(
        private readonly SeoMetaRepository $repository,
        private readonly SeoFallbackResolver $fallbackResolver,
        private readonly HreflangGenerator $hreflangGenerator,
        private readonly SeoRenderer $renderer,
    ) {}

    public function for(Model $model, ?string $locale = null): SeoData
    {
        $data = $this->fallbackResolver->resolve($model, $this->repository->findFor($model, $locale), $locale);

        return SeoData::fromArray(array_replace($data->toArray(), [
            'alternates' => $this->hreflangGenerator->for($model),
        ]));
    }

    public function render(Model|SeoData|array $source, ?string $locale = null): HtmlString
    {
        if ($source instanceof Model) {
            return $this->renderer->render($this->for($source, $locale));
        }

        return $this->renderer->render($source instanceof SeoData ? $source : $this->fromArray($source));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function fromArray(array $data): SeoData
    {
        return SeoData::fromArray($data);
    }
}
