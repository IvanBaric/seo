<?php

declare(strict_types=1);

namespace IvanBaric\Seo\Support;

final class SchemaJson
{
    /**
     * @param  array<string, mixed>  $schema
     */
    public static function encode(array $schema): string
    {
        return (string) json_encode(
            $schema,
            JSON_UNESCAPED_SLASHES
            | JSON_UNESCAPED_UNICODE
            | JSON_HEX_TAG
            | JSON_HEX_AMP
            | JSON_HEX_APOS
            | JSON_HEX_QUOT
        );
    }
}
