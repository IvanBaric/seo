<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use IvanBaric\Corexis\Concerns\BelongsToTenant;
use IvanBaric\Corexis\Concerns\HasUuid;
use IvanBaric\Seo\Support\SeoModels;
use IvanBaric\Seo\Support\SeoRobots;

/**
 * @property int $id
 * @property string $uuid
 * @property string $unique_key
 * @property int|string|null $team_id
 * @property string $seoable_type
 * @property int|string $seoable_id
 * @property string $locale
 * @property string|null $title
 * @property string|null $description
 * @property array<int, string>|null $keywords
 * @property string|null $canonical_url
 * @property string|null $robots
 * @property string|null $og_title
 * @property string|null $og_description
 * @property string|null $og_image
 * @property string|null $og_type
 * @property string|null $twitter_title
 * @property string|null $twitter_description
 * @property string|null $twitter_image
 * @property string|null $twitter_card
 * @property array<string, mixed>|null $schema
 * @property array<string, mixed>|null $metadata
 */
class SeoMeta extends Model
{
    use BelongsToTenant;
    use HasUuid;

    protected $guarded = ['id', 'uuid'];

    protected function casts(): array
    {
        return [
            'keywords' => 'array',
            'schema' => 'array',
            'metadata' => 'array',
        ];
    }

    public function getTable(): string
    {
        return SeoModels::table();
    }

    public function getConnectionName(): ?string
    {
        return config('seo.table.connection') ?: parent::getConnectionName();
    }

    /** @return MorphTo<Model, $this> */
    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isIndexable(): bool
    {
        return SeoRobots::isIndexable((string) ($this->robots ?: config('seo.defaults.robots', 'index,follow')));
    }

    /**
     * @return array<int, string>
     */
    public function robotsArray(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $this->robots))));
    }

    public function hasManualValue(string $field): bool
    {
        return array_key_exists($field, $this->attributes)
            && $this->attributes[$field] !== null
            && $this->attributes[$field] !== ''
            && $this->attributes[$field] !== '[]';
    }
}
