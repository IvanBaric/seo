<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

use InvalidArgumentException;

final class SitemapWritePath
{
    public function resolve(string $path): string
    {
        $path = $this->relativePath($path);
        $directory = $this->relativePath((string) config('seo.sitemap.write_directory', 'public'));

        if ($path !== $directory && ! str_starts_with($path, $directory.'/')) {
            throw new InvalidArgumentException(__('Sitemap je moguće zapisati samo unutar dopuštenog direktorija.'));
        }

        return base_path(str_replace('/', DIRECTORY_SEPARATOR, $path));
    }

    private function relativePath(string $path): string
    {
        $path = trim(str_replace('\\', '/', $path), '/');

        if (
            $path === ''
            || str_contains($path, "\0")
            || preg_match('/^[A-Za-z]:/', $path) === 1
            || str_starts_with($path, '//')
            || in_array('..', explode('/', $path), true)
        ) {
            throw new InvalidArgumentException(__('Putanja za sitemap nije valjana.'));
        }

        $normalized = implode('/', array_values(array_filter(
            explode('/', $path),
            static fn (string $segment): bool => $segment !== '' && $segment !== '.',
        )));

        if ($normalized === '') {
            throw new InvalidArgumentException(__('Putanja za sitemap nije valjana.'));
        }

        return $normalized;
    }
}
