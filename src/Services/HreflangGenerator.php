<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Services;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Seo\Contracts\SeoUrlResolver;
use IvanBaric\Seo\Data\AlternateUrlData;
use IvanBaric\Seo\Support\SeoContext;
use IvanBaric\Seo\Support\SeoValueNormalizer;

final class HreflangGenerator
{
    public function __construct(
        private readonly SeoContext $context,
        private readonly SeoUrlResolver $urlResolver,
        private readonly SeoValueNormalizer $normalizer,
    ) {}

    /**
     * @return array<int, AlternateUrlData>
     */
    public function for(Model $model): array
    {
        if (! (bool) config('seo.hreflang.enabled', true)) {
            return [];
        }

        $alternates = [];

        foreach ($this->context->availableLocales() as $locale) {
            $url = $this->normalizer->url($this->urlResolver->alternate($model, $locale));

            if ($url !== null) {
                $alternates[] = new AlternateUrlData($locale, $url);
            }
        }

        if ((bool) config('seo.hreflang.include_x_default', true)) {
            $default = $this->context->defaultLocale();
            $url = $default ? $this->normalizer->url($this->urlResolver->alternate($model, $default)) : null;

            if ($url !== null) {
                $alternates[] = new AlternateUrlData('x-default', $url);
            }
        }

        return $alternates;
    }
}
