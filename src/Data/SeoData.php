<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Data;

use IvanBaric\Seo\Support\SeoRobots;

final readonly class SeoData
{
    /**
     * @param  array<int, string>  $keywords
     * @param  array<string, mixed>  $schema
     * @param  array<string, mixed>  $metadata
     * @param  array<int, AlternateUrlData|array{locale: string, url: string}>  $alternates
     */
    public function __construct(
        public ?string $title = null,
        public ?string $description = null,
        public array $keywords = [],
        public ?string $canonicalUrl = null,
        public string $robots = 'index,follow',
        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
        public ?string $ogImage = null,
        public ?string $ogImageAlt = null,
        public string $ogType = 'website',
        public ?string $ogSiteName = null,
        public ?string $twitterTitle = null,
        public ?string $twitterDescription = null,
        public ?string $twitterImage = null,
        public ?string $twitterImageAlt = null,
        public string $twitterCard = 'summary_large_image',
        public array $schema = [],
        public array $metadata = [],
        public array $alternates = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            title: self::nullableString($data['title'] ?? null),
            description: self::nullableString($data['description'] ?? null),
            keywords: self::arrayOfStrings($data['keywords'] ?? []),
            canonicalUrl: self::nullableString($data['canonical_url'] ?? $data['canonicalUrl'] ?? null),
            robots: (string) ($data['robots'] ?? config('seo.defaults.robots', 'index,follow')),
            ogTitle: self::nullableString($data['og_title'] ?? $data['ogTitle'] ?? null),
            ogDescription: self::nullableString($data['og_description'] ?? $data['ogDescription'] ?? null),
            ogImage: self::nullableString($data['og_image'] ?? $data['ogImage'] ?? null),
            ogImageAlt: self::nullableString($data['og_image_alt'] ?? $data['ogImageAlt'] ?? null),
            ogType: (string) ($data['og_type'] ?? $data['ogType'] ?? config('seo.defaults.og_type', 'website')),
            ogSiteName: self::nullableString($data['og_site_name'] ?? $data['ogSiteName'] ?? null),
            twitterTitle: self::nullableString($data['twitter_title'] ?? $data['twitterTitle'] ?? null),
            twitterDescription: self::nullableString($data['twitter_description'] ?? $data['twitterDescription'] ?? null),
            twitterImage: self::nullableString($data['twitter_image'] ?? $data['twitterImage'] ?? null),
            twitterImageAlt: self::nullableString($data['twitter_image_alt'] ?? $data['twitterImageAlt'] ?? null),
            twitterCard: (string) ($data['twitter_card'] ?? $data['twitterCard'] ?? config('seo.defaults.twitter_card', 'summary_large_image')),
            schema: is_array($data['schema'] ?? null) ? $data['schema'] : [],
            metadata: is_array($data['metadata'] ?? null) ? $data['metadata'] : [],
            alternates: self::alternates($data['alternates'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->description,
            'keywords' => $this->keywords,
            'canonical_url' => $this->canonicalUrl,
            'robots' => $this->robots,
            'og_title' => $this->ogTitle,
            'og_description' => $this->ogDescription,
            'og_image' => $this->ogImage,
            'og_image_alt' => $this->ogImageAlt,
            'og_type' => $this->ogType,
            'og_site_name' => $this->ogSiteName,
            'twitter_title' => $this->twitterTitle,
            'twitter_description' => $this->twitterDescription,
            'twitter_image' => $this->twitterImage,
            'twitter_image_alt' => $this->twitterImageAlt,
            'twitter_card' => $this->twitterCard,
            'schema' => $this->schema,
            'metadata' => $this->metadata,
            'alternates' => array_map(
                static fn (AlternateUrlData|array $alternate): array => $alternate instanceof AlternateUrlData ? $alternate->toArray() : $alternate,
                $this->alternates
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $defaults
     */
    public function withDefaults(array $defaults): self
    {
        return self::fromArray(array_replace($defaults, array_filter($this->toArray(), static fn (mixed $value): bool => $value !== null && $value !== [])));
    }

    public function isIndexable(): bool
    {
        return SeoRobots::isIndexable($this->robots);
    }

    private static function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    /**
     * @return array<int, string>
     */
    private static function arrayOfStrings(mixed $value): array
    {
        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(
            static fn (mixed $item): string => trim((string) $item),
            $value
        ), static fn (string $item): bool => $item !== '')));
    }

    /**
     * @return array<int, AlternateUrlData>
     */
    private static function alternates(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_filter(array_map(static function (mixed $alternate): ?AlternateUrlData {
            if ($alternate instanceof AlternateUrlData) {
                return $alternate;
            }

            if (is_array($alternate) && isset($alternate['locale'], $alternate['url'])) {
                return AlternateUrlData::fromArray($alternate);
            }

            return null;
        }, $value)));
    }
}
