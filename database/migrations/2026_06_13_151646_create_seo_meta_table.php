<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use IvanBaric\Seo\Support\SeoConfigResolver;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('seo.table.connection');
        $tableName = SeoConfigResolver::metaTable();
        $tenantIdColumn = (string) config('corexis.tenancy.id_column', 'team_id');
        $defaultLocaleKey = (string) config('seo.locale.default_locale_key', '__default');

        if (Schema::connection($connection)->hasTable($tableName)) {
            return;
        }

        Schema::connection($connection)->create($tableName, function (Blueprint $table) use ($defaultLocaleKey, $tenantIdColumn): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('unique_key', 64)->unique();
            $table->string($tenantIdColumn)->nullable()->index();
            $table->string('seoable_type')->index();
            $table->unsignedBigInteger('seoable_id')->index();
            $table->string('locale')->default($defaultLocaleKey)->index();
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

            $table->index([$tenantIdColumn, 'seoable_type', 'seoable_id', 'locale']);
            $table->index('updated_at');
        });
    }

    public function down(): void
    {
        Schema::connection(config('seo.table.connection'))->dropIfExists(SeoConfigResolver::metaTable());
    }
};
