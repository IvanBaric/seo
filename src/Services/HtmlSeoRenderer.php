<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Services;

use Illuminate\Support\HtmlString;
use IvanBaric\Seo\Contracts\SeoRenderer;
use IvanBaric\Seo\Data\AlternateUrlData;
use IvanBaric\Seo\Data\SeoData;
use IvanBaric\Seo\Support\SchemaJson;
use IvanBaric\Seo\Support\SeoValueNormalizer;

final class HtmlSeoRenderer implements SeoRenderer
{
    public function __construct(private readonly SeoValueNormalizer $normalizer) {}

    public function render(SeoData $data): HtmlString
    {
        $lines = [];

        $this->tag($lines, 'title', $data->title);
        $this->meta($lines, 'name', 'description', $data->description);
        $this->meta($lines, 'name', 'keywords', $data->keywords === [] ? null : implode(', ', $data->keywords));
        $this->meta($lines, 'name', 'robots', $data->robots);

        if ((bool) config('seo.canonical.enabled', true)) {
            $canonical = $this->normalizer->url($data->canonicalUrl);

            if ($canonical !== null) {
                $lines[] = '<link rel="canonical" href="'.$this->e($canonical).'">';
            }
        }

        if ((bool) config('seo.open_graph.enabled', true)) {
            $this->meta($lines, 'property', 'og:title', $data->ogTitle);
            $this->meta($lines, 'property', 'og:description', $data->ogDescription);
            $this->meta($lines, 'property', 'og:image', $this->normalizer->url($data->ogImage));
            $this->meta($lines, 'property', 'og:type', $data->ogType);
            $this->meta($lines, 'property', 'og:url', $this->normalizer->url($data->canonicalUrl));
        }

        if ((bool) config('seo.twitter.enabled', true)) {
            $this->meta($lines, 'name', 'twitter:card', $data->twitterCard);
            $this->meta($lines, 'name', 'twitter:title', $data->twitterTitle);
            $this->meta($lines, 'name', 'twitter:description', $data->twitterDescription);
            $this->meta($lines, 'name', 'twitter:image', $this->normalizer->url($data->twitterImage));
        }

        foreach ($data->alternates as $alternate) {
            $alternate = $alternate instanceof AlternateUrlData ? $alternate : AlternateUrlData::fromArray($alternate);
            $url = $this->normalizer->url($alternate->url);

            if ($alternate->locale !== '' && $url !== null) {
                $lines[] = '<link rel="alternate" hreflang="'.$this->e($alternate->locale).'" href="'.$this->e($url).'">';
            }
        }

        if ((bool) config('seo.schema.enabled', true) && $data->schema !== []) {
            $lines[] = '<script type="application/ld+json">'.SchemaJson::encode($data->schema).'</script>';
        }

        return new HtmlString(implode(PHP_EOL, array_values(array_unique($lines))));
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function tag(array &$lines, string $tag, ?string $value): void
    {
        if ($value !== null && $value !== '') {
            $lines[] = "<{$tag}>".$this->e($value)."</{$tag}>";
        }
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function meta(array &$lines, string $key, string $name, ?string $content): void
    {
        if ($content !== null && $content !== '') {
            $lines[] = '<meta '.$key.'="'.$this->e($name).'" content="'.$this->e($content).'">';
        }
    }

    private function e(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false);
    }
}
