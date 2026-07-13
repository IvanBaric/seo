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
        $tenantIdColumn = (string) config('corexis.tenancy.id_column', 'team_id');

        if (! Schema::connection($connection)->hasTable($tableName)) {
            return;
        }

        if (! Schema::connection($connection)->hasColumn($tableName, $tenantIdColumn)) {
            Schema::connection($connection)->table($tableName, function (Blueprint $table) use ($tenantIdColumn): void {
                $table->string($tenantIdColumn)->nullable()->after('unique_key')->index();
            });
        }

        if ($tenantIdColumn !== 'tenant_id' && Schema::connection($connection)->hasColumn($tableName, 'tenant_id')) {
            DB::connection($connection)
                ->table($tableName)
                ->whereNull($tenantIdColumn)
                ->update([$tenantIdColumn => DB::raw('tenant_id')]);
        }

    }

    public function down(): void {}
};
