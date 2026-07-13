<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Seo\Support\SeoConfigResolver;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('seo.table.connection');
        $tableName = SeoConfigResolver::metaTable();

        if (! Schema::connection($connection)->hasTable($tableName)) {
            return;
        }

        DB::connection($connection)
            ->table($tableName)
            ->whereNull('locale')
            ->update(['locale' => (string) config('seo.locale.default_locale_key', '__default')]);

        $legacyColumns = array_values(array_filter(
            ['tenant_type', 'tenant_uuid', 'seoable_uuid'],
            fn (string $column): bool => Schema::connection($connection)->hasColumn($tableName, $column),
        ));

        if ($legacyColumns !== []) {
            $legacyIndexes = collect(Schema::connection($connection)->getIndexes($tableName))
                ->filter(function (array $index) use ($legacyColumns): bool {
                    $columns = $index['columns'] ?? [];

                    return is_array($columns) && array_intersect($legacyColumns, $columns) !== [];
                })
                ->pluck('name')
                ->filter(static fn (mixed $name): bool => is_string($name))
                ->values()
                ->all();

            if ($legacyIndexes !== []) {
                $this->dropLegacyIndexes($connection, $tableName, $legacyIndexes);
            }

            Schema::connection($connection)->table($tableName, function (Blueprint $table) use ($legacyColumns): void {
                $table->dropColumn($legacyColumns);
            });
        }
    }

    public function down(): void
    {
        $connection = config('seo.table.connection');
        $tableName = SeoConfigResolver::metaTable();

        if (! Schema::connection($connection)->hasTable($tableName)) {
            return;
        }

        $missing = array_values(array_filter(
            ['tenant_type', 'tenant_uuid', 'seoable_uuid'],
            fn (string $column): bool => ! Schema::connection($connection)->hasColumn($tableName, $column),
        ));

        if ($missing === []) {
            return;
        }

        Schema::connection($connection)->table($tableName, function (Blueprint $table) use ($missing): void {
            if (in_array('tenant_type', $missing, true)) {
                $table->string('tenant_type')->nullable()->index();
            }

            if (in_array('tenant_uuid', $missing, true)) {
                $table->uuid('tenant_uuid')->nullable()->index();
            }

            if (in_array('seoable_uuid', $missing, true)) {
                $table->uuid('seoable_uuid')->nullable()->index();
            }
        });
    }

    /**
     * @param  array<int, string>  $indexes
     */
    private function dropLegacyIndexes(?string $connection, string $tableName, array $indexes): void
    {
        if (Schema::connection($connection)->getConnection()->getDriverName() === 'sqlite') {
            foreach ($indexes as $index) {
                DB::connection($connection)->statement(sprintf(
                    'drop index if exists "%s"',
                    str_replace('"', '""', $index),
                ));
            }

            return;
        }

        Schema::connection($connection)->table($tableName, function (Blueprint $table) use ($indexes): void {
            foreach ($indexes as $index) {
                $table->dropIndex($index);
            }
        });
    }
};
