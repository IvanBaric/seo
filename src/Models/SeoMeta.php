<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Str;
use IvanBaric\Seo\Support\SeoRobots;

class SeoMeta extends Model
{
    protected $guarded = [];

    protected $casts = [
        'keywords' => 'array',
        'schema' => 'array',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (SeoMeta $meta): void {
            $column = (string) config('seo.uuid.column', 'uuid');

            if ((bool) config('seo.uuid.enabled', true) && empty($meta->{$column})) {
                $meta->{$column} = (string) Str::uuid();
            }
        });
    }

    public function getTable(): string
    {
        return (string) config('seo.table.name', parent::getTable());
    }

    public function getConnectionName(): ?string
    {
        return config('seo.table.connection') ?: parent::getConnectionName();
    }

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
