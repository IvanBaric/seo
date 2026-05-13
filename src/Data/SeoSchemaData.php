<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Data;

final readonly class SeoSchemaData
{
    /**
     * @param  array<string, mixed>  $schema
     */
    public function __construct(public array $schema) {}

    /**
     * @param  array<string, mixed>  $schema
     */
    public static function fromArray(array $schema): self
    {
        return new self($schema);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->schema;
    }
}
