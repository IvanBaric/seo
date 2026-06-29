<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('seo.table.connection');
        $tableName = config('seo.table.name', 'seo_meta');
        $tenantIdColumn = (string) config('seo.tenant.id_column', 'team_id');

        if (! Schema::connection($connection)->hasTable($tableName)) {
            return;
        }

        if (! Schema::connection($connection)->hasColumn($tableName, $tenantIdColumn)) {
            Schema::connection($connection)->table($tableName, function (Blueprint $table) use ($tenantIdColumn): void {
                $table->string($tenantIdColumn)->nullable()->after('tenant_type')->index();
            });
        }

        if ($tenantIdColumn !== 'tenant_id' && Schema::connection($connection)->hasColumn($tableName, 'tenant_id')) {
            DB::connection($connection)
                ->table($tableName)
                ->whereNull($tenantIdColumn)
                ->update([$tenantIdColumn => DB::raw('tenant_id')]);
        }

        if (! $this->hasIndex($connection, $tableName, $this->tenantIndexName($tenantIdColumn))) {
            Schema::connection($connection)->table($tableName, function (Blueprint $table): void {
                $tenantIdColumn = (string) config('seo.tenant.id_column', 'team_id');

                $table->index(['tenant_type', $tenantIdColumn], $this->tenantIndexName($tenantIdColumn));
            });
        }
    }

    public function down(): void
    {
        $connection = config('seo.table.connection');
        $tableName = config('seo.table.name', 'seo_meta');
        $tenantIdColumn = (string) config('seo.tenant.id_column', 'team_id');

        if ($tenantIdColumn === 'tenant_id' || ! Schema::connection($connection)->hasTable($tableName) || ! Schema::connection($connection)->hasColumn($tableName, $tenantIdColumn)) {
            return;
        }

        Schema::connection($connection)->table($tableName, function (Blueprint $table) use ($connection, $tableName, $tenantIdColumn): void {
            if ($this->hasIndex(config('seo.table.connection'), config('seo.table.name', 'seo_meta'), $this->tenantIndexName($tenantIdColumn))) {
                $table->dropIndex($this->tenantIndexName($tenantIdColumn));
            }

            if ($this->hasIndex($connection, $tableName, $this->defaultIndexName($tableName, [$tenantIdColumn], 'index'))) {
                $table->dropIndex([$tenantIdColumn]);
            }

            $table->dropColumn($tenantIdColumn);
        });
    }

    private function tenantIndexName(string $tenantIdColumn): string
    {
        return 'seo_meta_tenant_type_'.$tenantIdColumn.'_index';
    }

    private function hasIndex(?string $connection, string $table, string $index): bool
    {
        return collect(Schema::connection($connection)->getIndexes($table))
            ->contains(fn (array $definition): bool => ($definition['name'] ?? null) === $index);
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function defaultIndexName(string $table, array $columns, string $type): string
    {
        return strtolower($table.'_'.implode('_', $columns).'_'.$type);
    }
};
