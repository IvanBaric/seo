<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use IvanBaric\Corexis\Contracts\Events\DomainEvent;

final class SeoMetaDeleted implements DomainEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly int|string $metaId,
        public readonly string $seoableType,
        public readonly int|string $seoableId,
        public readonly ?string $locale,
    ) {}
}
