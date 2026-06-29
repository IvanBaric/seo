<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Seo\Events\SeoMetaDeleted;
use IvanBaric\Seo\Models\SeoMeta;
use IvanBaric\Seo\Services\SeoMetaRepository;

final readonly class DeleteSeoMetaAction
{
    public function __construct(
        private SeoMetaRepository $repository,
    ) {}

    public function handle(Model $model, ?string $locale = null): ActionResult
    {
        if ($result = corexis_authorization_result('seo.meta.delete', $model)) {
            return $result;
        }

        $meta = $this->repository->findFor($model, $locale);

        if (! $meta instanceof SeoMeta) {
            return ActionResult::error(
                message: __('SEO meta podaci nisu pronađeni.'),
                code: 'seo_meta_not_found',
            );
        }

        $metaKey = $meta->getKey();
        $metaId = is_int($metaKey) || is_string($metaKey) ? $metaKey : (string) $metaKey;
        $seoableType = (string) $meta->seoable_type;
        $rawSeoableId = $meta->seoable_id;
        $seoableId = is_int($rawSeoableId) || is_string($rawSeoableId) ? $rawSeoableId : (string) $rawSeoableId;
        $storedLocale = is_string($meta->locale) ? $meta->locale : null;

        DB::transaction(static function () use ($meta): void {
            /** @var SeoMeta $lockedMeta */
            $lockedMeta = SeoMeta::query()
                ->whereKey($meta->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            $lockedMeta->delete();
        });

        event(new SeoMetaDeleted($metaId, $seoableType, $seoableId, $storedLocale));

        return ActionResult::success(__('SEO meta podaci su obrisani.'));
    }
}
