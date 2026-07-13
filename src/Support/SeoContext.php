<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

use InvalidArgumentException;
use IvanBaric\Corexis\Contracts\LocaleResolver;
use IvanBaric\Corexis\Contracts\TenantResolver;

final readonly class SeoContext
{
    public function __construct(
        private TenantResolver $tenantResolver,
        private LocaleResolver $localeResolver,
    ) {}

    public function currentTenantId(): int|string|null
    {
        return $this->tenantResolver->enabled() ? $this->tenantResolver->id() : null;
    }

    public function currentLocale(): ?string
    {
        if (! (bool) config('seo.locale.enabled', true)) {
            return null;
        }

        if ($this->localeResolver->enabled()) {
            $current = $this->normalizeLocale($this->localeResolver->current());

            if ($current !== null) {
                return $current;
            }

            return (bool) config('seo.locale.fallback_to_default_locale', true) ? $this->defaultLocale() : null;
        }

        return (string) config('seo.locale.default_locale_key', '__default');
    }

    public function defaultLocale(): string
    {
        return $this->normalizeLocale($this->localeResolver->default())
            ?: (string) config('seo.locale.default_locale_key', '__default');
    }

    /**
     * @return array<int, string>
     */
    public function availableLocales(): array
    {
        if (! (bool) config('seo.locale.enabled', true)) {
            return [];
        }

        if (! $this->localeResolver->enabled()) {
            return [$this->defaultLocale()];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (?string $locale): ?string => $this->normalizeLocale($locale),
            $this->localeResolver->available()
        ))));
    }

    public function localeKey(?string $locale = null): string
    {
        $defaultKey = (string) config('seo.locale.default_locale_key', '__default');

        if (! (bool) config('seo.locale.enabled', true)) {
            return $defaultKey;
        }

        if ($locale !== null && $locale !== '') {
            $locale = $this->normalizeLocale($locale);

            if ($locale === null) {
                throw new InvalidArgumentException('The SEO locale must be a valid locale identifier.');
            }
        }

        $locale ??= $this->currentLocale();

        if ($locale === null || $locale === '') {
            return $defaultKey;
        }

        if (! (bool) config('seo.locale.store_default_locale', false) && $locale === $this->defaultLocale()) {
            return $defaultKey;
        }

        return $locale;
    }

    private function normalizeLocale(?string $locale): ?string
    {
        $locale = trim((string) $locale);

        if ($locale === '' || mb_strlen($locale) > 35 || preg_match('/^[A-Za-z0-9_-]+$/', $locale) !== 1) {
            return null;
        }

        return $locale;
    }
}
