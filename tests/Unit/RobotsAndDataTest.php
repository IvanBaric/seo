<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Unit;

use IvanBaric\Seo\Data\SeoData;
use IvanBaric\Seo\Exceptions\InvalidRobotsDirectiveException;
use IvanBaric\Seo\Support\SeoRobots;
use IvanBaric\Seo\Support\SeoValueNormalizer;
use IvanBaric\Seo\Tests\TestCase;

final class RobotsAndDataTest extends TestCase
{
    public function test_string_normalizer_resolves_translatable_values(): void
    {
        app()->setLocale('hr');

        $this->assertSame(
            'Hrvatski naslov',
            app(SeoValueNormalizer::class)->string([
                'en' => 'English title',
                'hr' => 'Hrvatski naslov',
            ]),
        );
    }

    public function test_robots_normalization_and_indexability(): void
    {
        $this->assertSame('index,follow', SeoRobots::make(['index', 'follow', 'follow']));
        $this->assertTrue(SeoRobots::isIndexable('index,follow'));
        $this->assertFalse(SeoRobots::isIndexable('noindex,nofollow'));
    }

    public function test_invalid_robots_directive_throws_exception(): void
    {
        $this->expectException(InvalidRobotsDirectiveException::class);

        SeoRobots::make('index,invalid');
    }

    public function test_seo_data_round_trips_typed_values(): void
    {
        $data = SeoData::fromArray([
            'title' => 'Title',
            'keywords' => 'one, two, one',
            'alternates' => [['locale' => 'hr', 'url' => 'https://example.test/hr']],
        ]);

        $this->assertSame(['one', 'two'], $data->keywords);
        $this->assertSame('Title', $data->toArray()['title']);
        $this->assertSame('hr', $data->toArray()['alternates'][0]['locale']);
    }
}
