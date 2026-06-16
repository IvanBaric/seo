<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Console;

use Illuminate\Console\Command;
use IvanBaric\Seo\Actions\RefreshSeoCacheAction;

final class ClearSeoCacheCommand extends Command
{
    protected $signature = 'seo:clear-cache';

    protected $description = 'Clear SEO package cache entries.';

    public function handle(RefreshSeoCacheAction $action): int
    {
        $result = $action->handle();

        if ($result->failed()) {
            $this->error($result->message);

            return self::FAILURE;
        }

        $this->info($result->message);

        return self::SUCCESS;
    }
}
