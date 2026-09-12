<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('seo_optimization_assets')
            || ! Schema::hasColumn('seo_optimization_assets', 'sheet_revision')
            || Schema::hasColumn('seo_optimization_assets', 'source_revision')) {
            return;
        }

        Schema::table('seo_optimization_assets', function (Blueprint $table): void {
            $table->renameColumn('sheet_revision', 'source_revision');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('seo_optimization_assets')
            || ! Schema::hasColumn('seo_optimization_assets', 'source_revision')
            || Schema::hasColumn('seo_optimization_assets', 'sheet_revision')) {
            return;
        }

        Schema::table('seo_optimization_assets', function (Blueprint $table): void {
            $table->renameColumn('source_revision', 'sheet_revision');
        });
    }
};
