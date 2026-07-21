<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Actions;

use Illuminate\Support\Facades\File;
use InvalidArgumentException;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Seo\Events\SitemapGenerated;
use IvanBaric\Seo\Services\SitemapGenerator;
use IvanBaric\Seo\Support\SitemapWritePath;
use Throwable;

final readonly class GenerateSitemapAction
{
    public function __construct(
        private SitemapGenerator $generator,
        private SitemapWritePath $writePath,
    ) {}

    public function handle(bool $fresh = false, bool $cache = true, ?string $writePath = null, bool $authorize = true): ActionResult
    {
        if ($authorize && ($result = corexis_authorization_result('seo.sitemap.generate'))) {
            return $result;
        }

        try {
            $xml = $this->generator->generate($fresh, $cache);

            if (is_string($writePath) && trim($writePath) !== '') {
                $path = $this->writePath->resolve($writePath);
                File::ensureDirectoryExists(dirname($path));
                File::put($path, $xml);
            }
        } catch (InvalidArgumentException $exception) {
            return ActionResult::error(
                message: $exception->getMessage(),
                code: 'invalid_sitemap_write_path',
            );
        } catch (Throwable $exception) {
            report($exception);

            return ActionResult::error(
                message: __('Sitemap trenutno nije moguće generirati.'),
                code: 'sitemap_generation_failed',
            );
        }

        event(new SitemapGenerated(strlen($xml), $fresh, $cache, $writePath));

        return ActionResult::success(
            message: __('Sitemap je generiran.'),
            data: [
                'xml' => $xml,
                'bytes' => strlen($xml),
                'written_to' => $writePath,
            ],
        );
    }
}
