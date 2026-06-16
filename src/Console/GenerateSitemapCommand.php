<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Console;

use Illuminate\Console\Command;
use IvanBaric\Seo\Actions\GenerateSitemapAction;

final class GenerateSitemapCommand extends Command
{
    protected $signature = 'seo:generate-sitemap {--fresh : Rebuild sitemap instead of using cache} {--write= : Write XML to a path, for example public/sitemap.xml} {--no-cache : Do not read or write sitemap cache}';

    protected $description = 'Generate the SEO sitemap XML.';

    public function handle(GenerateSitemapAction $action): int
    {
        $result = $action->handle(
            fresh: (bool) $this->option('fresh'),
            cache: ! (bool) $this->option('no-cache'),
            writePath: is_string($this->option('write')) ? (string) $this->option('write') : null,
        );

        if ($result->failed()) {
            $this->error($result->message);

            return self::FAILURE;
        }

        $data = is_array($result->data) ? $result->data : [];
        $write = $data['written_to'] ?? null;

        if (is_string($write) && $write !== '') {
            $this->info("Sitemap written to [{$write}].");

            return self::SUCCESS;
        }

        $this->line((string) ($data['xml'] ?? ''));

        return self::SUCCESS;
    }
}
