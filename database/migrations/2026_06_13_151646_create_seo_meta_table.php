<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('seo.table.connection');
        $tableName = config('seo.table.name', 'seo_meta');
        $tenantIdColumn = (string) config('seo.tenant.id_column', 'team_id');

        if (Schema::connection($connection)->hasTable($tableName)) {
            return;
        }

        Schema::connection($connection)->create($tableName, function (Blueprint $table) use ($tenantIdColumn): void {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->string('unique_key', 64)->unique();
            $table->string('tenant_type')->nullable()->index();
            $table->string($tenantIdColumn)->nullable()->index();
            $table->uuid('tenant_uuid')->nullable()->index();
            $table->string('seoable_type')->index();
            $table->unsignedBigInteger('seoable_id')->index();
            $table->uuid('seoable_uuid')->nullable()->index();
            $table->string('locale')->nullable()->index();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('keywords')->nullable();
            $table->text('canonical_url')->nullable();
            $table->string('robots')->nullable();
            $table->string('og_title')->nullable();
            $table->text('og_description')->nullable();
            $table->text('og_image')->nullable();
            $table->string('og_type')->nullable();
            $table->string('twitter_title')->nullable();
            $table->text('twitter_description')->nullable();
            $table->text('twitter_image')->nullable();
            $table->string('twitter_card')->nullable();
            $table->json('schema')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['seoable_type', 'seoable_id']);
            $table->index(['tenant_type', $tenantIdColumn]);
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::connection(config('seo.table.connection'))->dropIfExists(config('seo.table.name', 'seo_meta'));
    }
};
