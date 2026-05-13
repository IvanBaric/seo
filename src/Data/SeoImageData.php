<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Data;

final readonly class SeoImageData
{
    public function __construct(
        public ?string $url,
        public ?string $alt = null,
        public ?int $width = null,
        public ?int $height = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isset($data['url']) ? (string) $data['url'] : null,
            isset($data['alt']) ? (string) $data['alt'] : null,
            isset($data['width']) ? (int) $data['width'] : null,
            isset($data['height']) ? (int) $data['height'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'url' => $this->url,
            'alt' => $this->alt,
            'width' => $this->width,
            'height' => $this->height,
        ], static fn (mixed $value): bool => $value !== null);
    }
}
