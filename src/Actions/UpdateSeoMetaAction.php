<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Seo\Actions\Concerns\AuthorizesSeoActions;
use IvanBaric\Seo\Events\SeoMetaUpdated;
use IvanBaric\Seo\Services\SeoMetaRepository;
use IvanBaric\Seo\Support\SeoRobots;
use IvanBaric\Seo\Support\SeoSubjectResolver;
use IvanBaric\Seo\Support\SeoValueNormalizer;
use JsonException;
use Throwable;

final readonly class UpdateSeoMetaAction
{
    use AuthorizesSeoActions;

    public function __construct(
        private SeoMetaRepository $repository,
        private SeoSubjectResolver $subjectResolver,
        private SeoValueNormalizer $normalizer,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Model $model, array $data, ?string $locale = null): ActionResult
    {
        $model = $this->resolveAuthorizedSeoSubject($this->subjectResolver, $model, 'seo.meta.update');

        if ($model instanceof ActionResult) {
            return $model;
        }

        $validator = Validator::make(array_merge($data, ['locale' => $locale]), [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'keywords' => ['nullable', 'array', 'max:50'],
            'keywords.*' => ['string', 'max:100'],
            'canonical_url' => ['nullable', 'string', 'max:2048', $this->safeUrlRule()],
            'robots' => ['nullable', 'string', 'max:255', $this->robotsRule()],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:2048', $this->safeUrlRule()],
            'og_type' => ['nullable', 'string', 'max:100'],
            'twitter_title' => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string', 'max:500'],
            'twitter_image' => ['nullable', 'string', 'max:2048', $this->safeUrlRule()],
            'twitter_card' => ['nullable', 'string', 'max:100'],
            'schema' => ['nullable', 'array', 'max:100'],
            'metadata' => ['nullable', 'array', 'max:100'],
            'locale' => ['nullable', 'string', 'max:35', 'regex:/^[A-Za-z0-9_-]+$/'],
        ]);

        if ($validator->fails()) {
            return ActionResult::error(
                message: __('Provjerite SEO meta podatke i pokušajte ponovno.'),
                code: 'validation_failed',
                errors: $validator->errors()->toArray(),
            );
        }

        $validated = $validator->validated();
        unset($validated['locale']);

        try {
            json_encode([
                'schema' => $validated['schema'] ?? null,
                'metadata' => $validated['metadata'] ?? null,
            ], JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return ActionResult::error(
                message: __('Strukturirani SEO podaci nisu valjani.'),
                code: 'validation_failed',
                errors: ['schema' => [__('Strukturirani SEO podaci nisu valjani.')]],
            );
        }

        try {
            $meta = DB::transaction(fn () => $this->repository->update($model, $validated, $locale));
        } catch (Throwable $exception) {
            report($exception);

            return ActionResult::error(
                message: __('SEO meta podatke trenutno nije moguće spremiti.'),
                code: 'seo_update_failed',
            );
        }

        event(new SeoMetaUpdated($meta->refresh()));

        return ActionResult::success(
            message: __('SEO meta podaci su spremljeni.'),
            data: $meta,
        );
    }

    /** @return \Closure(string, mixed, \Closure(string): void): void */
    private function safeUrlRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value !== null && $value !== '' && $this->normalizer->url($value) === null) {
                $fail(__('URL nije valjan ili nije dopušten.'));
            }
        };
    }

    /** @return \Closure(string, mixed, \Closure(string): void): void */
    private function robotsRule(): \Closure
    {
        return static function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value === null || $value === '') {
                return;
            }

            try {
                SeoRobots::make((string) $value);
            } catch (Throwable) {
                $fail(__('Robots direktive nisu valjane.'));
            }
        };
    }
}
