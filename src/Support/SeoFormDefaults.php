<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Seo\Contracts\SeoUrlResolver;
use IvanBaric\Seo\Data\SeoFormState;

final class SeoFormDefaults
{
    private mixed $titleSource = null;

    private mixed $descriptionSource = null;

    private mixed $manualTitle = null;

    private mixed $manualDescription = null;

    private bool $manualTitleEnabled = false;

    private bool $manualDescriptionEnabled = false;

    private mixed $canonicalSource = null;

    private ?bool $indexed = null;

    private int $titleMaxLength;

    private int $descriptionMaxLength;

    public function __construct(
        private readonly ?Model $model = null,
        private readonly ?SeoValueNormalizer $normalizer = null,
        private readonly ?SeoUrlResolver $urlResolver = null,
    ) {
        $this->titleMaxLength = (int) config('seo.limits.title_max_length', 70);
        $this->descriptionMaxLength = (int) config('seo.limits.description_max_length', 160);
    }

    public static function for(?Model $model = null): self
    {
        return new self(
            model: $model,
            normalizer: app(SeoValueNormalizer::class),
            urlResolver: app(SeoUrlResolver::class),
        );
    }

    public function titleFrom(mixed $value): self
    {
        $this->titleSource = $value;

        return $this;
    }

    public function descriptionFrom(mixed $value): self
    {
        $this->descriptionSource = $value;

        return $this;
    }

    public function manualTitle(mixed $value, bool $enabled = true): self
    {
        $this->manualTitle = $value;
        $this->manualTitleEnabled = $enabled;

        return $this;
    }

    public function manualDescription(mixed $value, bool $enabled = true): self
    {
        $this->manualDescription = $value;
        $this->manualDescriptionEnabled = $enabled;

        return $this;
    }

    public function canonicalFrom(mixed $value): self
    {
        $this->canonicalSource = $value;

        return $this;
    }

    public function indexed(?bool $indexed): self
    {
        $this->indexed = $indexed;

        return $this;
    }

    public function titleMaxLength(int $length): self
    {
        $this->titleMaxLength = $length;

        return $this;
    }

    public function descriptionMaxLength(int $length): self
    {
        $this->descriptionMaxLength = $length;

        return $this;
    }

    public function resolve(): SeoFormState
    {
        return new SeoFormState(
            title: $this->manualTitleEnabled
                ? ($this->string($this->manualTitle, $this->titleMaxLength) ?? $this->automaticTitle())
                : $this->automaticTitle(),
            description: $this->manualDescriptionEnabled
                ? ($this->string($this->manualDescription, $this->descriptionMaxLength) ?? $this->automaticDescription())
                : $this->automaticDescription(),
            canonicalUrl: $this->canonicalUrl(),
            robots: $this->isIndexed() ? 'index,follow' : 'noindex,nofollow',
        );
    }

    private function automaticTitle(): ?string
    {
        return $this->string($this->titleSource, $this->titleMaxLength)
            ?? $this->string($this->modelDefault('title'), $this->titleMaxLength)
            ?? $this->modelAttribute((array) config('seo.fallbacks.title', []), $this->titleMaxLength);
    }

    private function automaticDescription(): ?string
    {
        return $this->string(strip_tags((string) $this->descriptionSource), $this->descriptionMaxLength)
            ?? $this->string($this->modelDefault('description'), $this->descriptionMaxLength)
            ?? $this->modelAttribute((array) config('seo.fallbacks.description', []), $this->descriptionMaxLength);
    }

    private function canonicalUrl(): ?string
    {
        return $this->normalizer()->url($this->canonicalSource)
            ?? ($this->model ? $this->urlResolver()->resolve($this->model) : null);
    }

    private function isIndexed(): bool
    {
        if ($this->indexed !== null) {
            return $this->indexed;
        }

        if ($this->model && method_exists($this->model, 'shouldBeIndexed')) {
            return (bool) $this->model->shouldBeIndexed();
        }

        return true;
    }

    private function modelDefault(string $key): mixed
    {
        if (! $this->model || ! method_exists($this->model, 'seoDefaults')) {
            return null;
        }

        $defaults = $this->model->seoDefaults();

        return is_array($defaults) ? ($defaults[$key] ?? null) : null;
    }

    /**
     * @param  array<int, string>  $attributes
     */
    private function modelAttribute(array $attributes, int $limit): ?string
    {
        if (! $this->model) {
            return null;
        }

        foreach ($attributes as $attribute) {
            $value = OptionalModelAttribute::get($this->model, $attribute);

            if ($value !== null && $value !== '') {
                return $this->string($value, $limit);
            }
        }

        return null;
    }

    private function string(mixed $value, int $limit): ?string
    {
        return $this->normalizer()->string($value, $limit);
    }

    private function normalizer(): SeoValueNormalizer
    {
        return $this->normalizer ?? app(SeoValueNormalizer::class);
    }

    private function urlResolver(): SeoUrlResolver
    {
        return $this->urlResolver ?? app(SeoUrlResolver::class);
    }
}
