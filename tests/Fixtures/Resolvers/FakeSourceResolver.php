<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Fixtures\Resolvers;

use IvanBaric\Corexis\Contracts\SourceResolver;

final class FakeSourceResolver implements SourceResolver
{
    public function current(): string
    {
        return 'web';
    }

    public function allowed(): array
    {
        return ['web', 'cli'];
    }

    public function isAllowed(string $source): bool
    {
        return in_array($source, $this->allowed(), true);
    }
}
