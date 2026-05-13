<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

use IvanBaric\Seo\Exceptions\InvalidRobotsDirectiveException;

final class SeoRobots
{
    /**
     * @param  string|array<int, string>  $directives
     */
    public static function make(string|array $directives): string
    {
        $items = is_array($directives) ? $directives : explode(',', $directives);
        $items = array_values(array_unique(array_filter(array_map(
            static fn (string $directive): string => strtolower(trim($directive)),
            $items
        ), static fn (string $directive): bool => $directive !== '')));

        $robots = implode(',', $items);
        self::validate($robots);

        return $robots;
    }

    public static function isIndexable(string $robots): bool
    {
        $directives = array_map('trim', explode(',', strtolower($robots)));

        return ! in_array('noindex', $directives, true);
    }

    public static function validate(string $robots): void
    {
        $allowed = config('seo.robots.allowed', []);

        foreach (array_filter(array_map('trim', explode(',', strtolower($robots)))) as $directive) {
            if (! in_array($directive, $allowed, true)) {
                throw new InvalidRobotsDirectiveException("Invalid robots directive [{$directive}].");
            }
        }
    }
}
