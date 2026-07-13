<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Fixtures\Resolvers;

use IvanBaric\Corexis\Contracts\LocaleResolver;

final class FakeLocaleResolver implements LocaleResolver
{
    public function enabled(): bool
    {
        return true;
    }

    public function current(): string
    {
        return 'hr';
    }

    public function default(): string
    {
        return 'en';
    }

    public function fallback(): string
    {
        return 'en';
    }

    public function available(): array
    {
        return ['en', 'hr'];
    }
}
