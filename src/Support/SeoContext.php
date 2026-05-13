<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

use Illuminate\Contracts\Container\Container;
use IvanBaric\Corexis\Contracts\ActorResolver;
use IvanBaric\Corexis\Contracts\LocaleResolver;
use IvanBaric\Corexis\Contracts\SourceResolver;
use IvanBaric\Corexis\Contracts\TenantResolver;

final class SeoContext
{
    public function __construct(private readonly Container $container) {}

    public function currentTenantId(): int|string|null
    {
        $resolver = $this->tenantResolver();

        return $resolver?->enabled() ? $resolver->id() : null;
    }

    public function currentTenantType(): ?string
    {
        $resolver = $this->tenantResolver();

        return $resolver?->enabled() ? $resolver->type() : null;
    }

    public function currentTenantUuid(): ?string
    {
        $resolver = $this->tenantResolver();

        return $resolver?->enabled() ? $resolver->uuid() : null;
    }

    public function currentLocale(): ?string
    {
        if (! (bool) config('seo.locale.enabled', true)) {
            return null;
        }

        $resolver = $this->localeResolver();

        if ($resolver?->enabled()) {
            $current = $this->normalizeLocale($resolver->current());

            if ($current !== null) {
                return $current;
            }

            return (bool) config('seo.locale.fallback_to_default_locale', true) ? $this->defaultLocale() : null;
        }

        return (string) config('seo.locale.default_locale_key', '__default');
    }

    public function defaultLocale(): ?string
    {
        $resolver = $this->localeResolver();

        return $this->normalizeLocale($resolver?->default()) ?: (string) config('seo.locale.default_locale_key', '__default');
    }

    /**
     * @return array<int, string>
     */
    public function availableLocales(): array
    {
        if (! (bool) config('seo.locale.enabled', true)) {
            return [];
        }

        $resolver = $this->localeResolver();

        if (! $resolver?->enabled()) {
            return [$this->defaultLocale()];
        }

        return array_values(array_unique(array_filter(array_map(
            fn (?string $locale): ?string => $this->normalizeLocale($locale),
            $resolver->available()
        ))));
    }

    public function currentActorId(): int|string|null
    {
        $resolver = $this->actorResolver();

        return $resolver?->enabled() ? $resolver->id() : null;
    }

    public function currentSource(): ?string
    {
        return $this->sourceResolver()?->current();
    }

    public function localeKey(?string $locale = null): string
    {
        $defaultKey = (string) config('seo.locale.default_locale_key', '__default');

        if (! (bool) config('seo.locale.enabled', true)) {
            return $defaultKey;
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

    private function tenantResolver(): ?TenantResolver
    {
        return $this->container->bound(TenantResolver::class) ? $this->container->make(TenantResolver::class) : null;
    }

    private function localeResolver(): ?LocaleResolver
    {
        return $this->container->bound(LocaleResolver::class) ? $this->container->make(LocaleResolver::class) : null;
    }

    private function actorResolver(): ?ActorResolver
    {
        return $this->container->bound(ActorResolver::class) ? $this->container->make(ActorResolver::class) : null;
    }

    private function sourceResolver(): ?SourceResolver
    {
        return $this->container->bound(SourceResolver::class) ? $this->container->make(SourceResolver::class) : null;
    }

    private function normalizeLocale(?string $locale): ?string
    {
        $locale = trim((string) $locale);

        return $locale === '' ? null : $locale;
    }
}
