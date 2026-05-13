<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Resolvers;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Seo\Contracts\SeoSchemaBuilder;
use IvanBaric\Seo\Data\SeoData;

final class DefaultSeoSchemaBuilder implements SeoSchemaBuilder
{
    public function build(Model|array|SeoData $source): array
    {
        $data = $source instanceof SeoData ? $source->toArray() : (is_array($source) ? $source : $this->modelData($source));

        $schema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => $data['schema_type'] ?? 'WebPage',
            'name' => $data['title'] ?? null,
            'description' => $data['description'] ?? null,
            'url' => $data['canonical_url'] ?? null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '');

        if ($source instanceof Model && method_exists($source, 'seoSchema') && (bool) config('seo.schema.merge_model_schema', true)) {
            return array_replace($schema, $source->seoSchema());
        }

        return $schema;
    }

    /**
     * @return array<string, mixed>
     */
    private function modelData(Model $model): array
    {
        return [
            'title' => $model->getAttribute('title') ?: $model->getAttribute('name'),
            'description' => $model->getAttribute('description') ?: $model->getAttribute('excerpt'),
            'canonical_url' => method_exists($model, 'seoCanonicalUrl') ? $model->seoCanonicalUrl() : null,
        ];
    }
}
