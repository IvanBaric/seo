<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Tests\Fixtures\Resolvers;

use IvanBaric\Corexis\Contracts\ActorResolver;

final class FakeActorResolver implements ActorResolver
{
    public function enabled(): bool
    {
        return true;
    }

    public function current(): mixed
    {
        return ['id' => 5];
    }

    public function id(): int|string|null
    {
        return 5;
    }

    public function type(): ?string
    {
        return 'user';
    }
}
