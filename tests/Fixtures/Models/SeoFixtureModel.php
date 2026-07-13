<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use IvanBaric\Corexis\Concerns\BelongsToTenant;
use IvanBaric\Seo\Concerns\HasSeo;

/**
 * @property int $id
 * @property int|string|null $team_id
 * @property string|null $title
 * @property string|null $description
 * @property string|null $image_url
 * @property bool $indexed
 * @property bool $published
 */
final class SeoFixtureModel extends Model
{
    use BelongsToTenant;
    use HasSeo;

    protected $guarded = ['id', 'team_id'];

    /** @param Builder<self> $query */
    #[Scope]
    protected function published(Builder $query): void
    {
        $query->where('published', true);
    }

    public function getRouteKeyName(): string
    {
        return 'id';
    }

    /**
     * @return array<string, mixed>
     */
    public function seoDefaults(): array
    {
        return [
            'description' => 'Default fixture description',
            'image' => '/default-fixture.jpg',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function seoSchema(): array
    {
        return ['@type' => 'Article'];
    }

    public function seoCanonicalUrl(): ?string
    {
        return $this->exists ? '/fixtures/'.$this->getKey() : null;
    }

    public function seoImageUrl(): ?string
    {
        $image = $this->getAttribute('image_url');

        return is_string($image) ? $image : null;
    }

    public function shouldBeIndexed(): bool
    {
        return (bool) $this->getAttribute('indexed');
    }
}
