<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Data;

final readonly class AlternateUrlData
{
    public function __construct(
        public string $locale,
        public string $url,
    ) {}

    /**
     * @param  array{locale?: string, url?: string}  $data
     */
    public static function fromArray(array $data): self
    {
        return new self((string) ($data['locale'] ?? ''), (string) ($data['url'] ?? ''));
    }

    /**
     * @return array{locale: string, url: string}
     */
    public function toArray(): array
    {
        return ['locale' => $this->locale, 'url' => $this->url];
    }
}
