<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Seo\Concerns\HasSeo;

final class SeoFixtureModel extends Model
{
    use HasSeo;

    protected $guarded = [];

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
        return $this->image_url;
    }

    public function shouldBeIndexed(): bool
    {
        return (bool) $this->indexed;
    }
}
