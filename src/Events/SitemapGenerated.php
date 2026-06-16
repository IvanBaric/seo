<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IvanBaric\Corexis\Contracts\Events\DomainEvent;

final class SitemapGenerated implements DomainEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int $bytes,
        public readonly bool $fresh,
        public readonly bool $cache,
        public readonly ?string $writtenTo = null,
    ) {}
}
