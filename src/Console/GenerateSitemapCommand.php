<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use IvanBaric\Seo\Services\SitemapGenerator;

final class GenerateSitemapCommand extends Command
{
    protected $signature = 'seo:generate-sitemap {--fresh : Rebuild sitemap instead of using cache} {--write= : Write XML to a path, for example public/sitemap.xml} {--no-cache : Do not read or write sitemap cache}';

    protected $description = 'Generate the SEO sitemap XML.';

    public function handle(SitemapGenerator $generator): int
    {
        $xml = $generator->generate(
            fresh: (bool) $this->option('fresh'),
            cache: ! (bool) $this->option('no-cache'),
        );

        $write = $this->option('write');

        if (is_string($write) && $write !== '') {
            $path = base_path($write);
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $xml);
            $this->info("Sitemap written to [{$write}].");

            return self::SUCCESS;
        }

        $this->line($xml);

        return self::SUCCESS;
    }
}
