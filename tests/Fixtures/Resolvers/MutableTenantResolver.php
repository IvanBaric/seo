<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Fixtures\Resolvers;

use IvanBaric\Corexis\Contracts\TenantResolver;

final class MutableTenantResolver implements TenantResolver
{
    public static int|string|null $tenantId = 10;

    public function enabled(): bool
    {
        return true;
    }

    public function current(): mixed
    {
        return self::$tenantId === null ? null : ['id' => self::$tenantId];
    }

    public function id(): int|string|null
    {
        return self::$tenantId;
    }

    public function uuid(): ?string
    {
        return null;
    }

    public function type(): string
    {
        return 'team';
    }
}
