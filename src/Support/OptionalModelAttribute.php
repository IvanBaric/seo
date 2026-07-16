<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

use Illuminate\Database\Eloquent\Model;

final class OptionalModelAttribute
{
    public static function get(Model $model, string $attribute): mixed
    {
        if (
            ! array_key_exists($attribute, $model->getAttributes())
            && ! $model->hasGetMutator($attribute)
            && ! $model->hasAttributeGetMutator($attribute)
        ) {
            return null;
        }

        return $model->getAttribute($attribute);
    }
}
