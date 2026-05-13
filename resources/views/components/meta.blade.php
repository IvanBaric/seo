@props([
    'model' => null,
    'data' => null,
    'locale' => null,
])

{!! seo()->render($data ?? $model, $locale) !!}
