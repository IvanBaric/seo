<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Seo\Events\SeoMetaUpdated;
use IvanBaric\Seo\Services\SeoMetaRepository;

final readonly class UpdateSeoMetaAction
{
    public function __construct(
        private SeoMetaRepository $repository,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(Model $model, array $data, ?string $locale = null): ActionResult
    {
        if ($result = corexis_authorization_result('seo.meta.update', $model)) {
            return $result;
        }

        if (! $model->exists) {
            return ActionResult::error(
                message: __('SEO meta podatke nije moguće spremiti za nespremljeni model.'),
                code: 'seo_model_not_persisted',
            );
        }

        $validator = Validator::make($data, [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:500'],
            'keywords' => ['nullable', 'array'],
            'canonical_url' => ['nullable', 'string', 'max:2048'],
            'robots' => ['nullable', 'string', 'max:255'],
            'og_title' => ['nullable', 'string', 'max:255'],
            'og_description' => ['nullable', 'string', 'max:500'],
            'og_image' => ['nullable', 'string', 'max:2048'],
            'og_type' => ['nullable', 'string', 'max:100'],
            'twitter_title' => ['nullable', 'string', 'max:255'],
            'twitter_description' => ['nullable', 'string', 'max:500'],
            'twitter_image' => ['nullable', 'string', 'max:2048'],
            'twitter_card' => ['nullable', 'string', 'max:100'],
            'schema' => ['nullable', 'array'],
            'metadata' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return ActionResult::error(
                message: __('Provjerite SEO meta podatke i pokušajte ponovno.'),
                code: 'validation_failed',
                errors: $validator->errors()->toArray(),
            );
        }

        $meta = DB::transaction(fn () => $this->repository->update($model, $validator->validated(), $locale));

        event(new SeoMetaUpdated($meta->refresh()));

        return ActionResult::success(
            message: __('SEO meta podaci su spremljeni.'),
            data: $meta,
        );
    }
}
