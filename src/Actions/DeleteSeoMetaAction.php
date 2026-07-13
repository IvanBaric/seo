<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Seo\Actions\Concerns\AuthorizesSeoActions;
use IvanBaric\Seo\Events\SeoMetaDeleted;
use IvanBaric\Seo\Models\SeoMeta;
use IvanBaric\Seo\Services\SeoMetaRepository;
use IvanBaric\Seo\Support\SeoSubjectResolver;
use Throwable;

final readonly class DeleteSeoMetaAction
{
    use AuthorizesSeoActions;

    public function __construct(
        private SeoMetaRepository $repository,
        private SeoSubjectResolver $subjectResolver,
    ) {}

    public function handle(Model $model, ?string $locale = null): ActionResult
    {
        $model = $this->resolveAuthorizedSeoSubject($this->subjectResolver, $model, 'seo.meta.delete');

        if ($model instanceof ActionResult) {
            return $model;
        }

        try {
            $meta = DB::transaction(fn (): ?SeoMeta => $this->repository->delete($model, $locale));
        } catch (Throwable $exception) {
            report($exception);

            return ActionResult::error(
                message: __('SEO meta podatke trenutno nije moguće obrisati.'),
                code: 'seo_delete_failed',
            );
        }

        if (! $meta instanceof SeoMeta) {
            return ActionResult::error(
                message: __('SEO meta podaci nisu pronađeni.'),
                code: 'seo_meta_not_found',
            );
        }

        event(new SeoMetaDeleted(
            $meta->getKey(),
            $meta->seoable_type,
            $meta->seoable_id,
            $meta->locale,
        ));

        return ActionResult::success(__('SEO meta podaci su obrisani.'));
    }
}
