<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Actions\Concerns;

use Illuminate\Database\Eloquent\Model;
use IvanBaric\Corexis\Data\ActionResult;
use IvanBaric\Seo\Support\SeoSubjectResolver;

trait AuthorizesSeoActions
{
    protected function resolveAuthorizedSeoSubject(
        SeoSubjectResolver $resolver,
        Model $model,
        string $ability,
    ): Model|ActionResult {
        $resolved = $resolver->resolve($model);

        if (! $resolved instanceof Model) {
            $message = __('Traženi zapis nije dostupan.');

            return ActionResult::error(
                message: $message,
                code: 'seo_model_unavailable',
                errors: ['authorization' => [$message]],
            );
        }

        if ($result = corexis_authorization_result($ability, $resolved)) {
            return $result;
        }

        return $resolved;
    }
}
