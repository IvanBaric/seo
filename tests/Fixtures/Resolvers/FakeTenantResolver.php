<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Fixtures\Resolvers;

use IvanBaric\Corexis\Contracts\TenantResolver;

final class FakeTenantResolver implements TenantResolver
{
    public function enabled(): bool
    {
        return true;
    }

    public function current(): mixed
    {
        return ['id' => 10];
    }

    public function id(): int|string|null
    {
        return 10;
    }

    public function uuid(): ?string
    {
        return '9f8d8576-5b13-4f4f-8a18-b1d6d0aa2e10';
    }

    public function type(): ?string
    {
        return 'team';
    }
}
