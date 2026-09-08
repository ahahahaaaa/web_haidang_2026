<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('content_categories') || Schema::hasColumn('content_categories', 'parent_id')) {
            return;
        }

        Schema::table('content_categories', function (Blueprint $table): void {
            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('content_categories')
                ->nullOnDelete();

            $table->index(
                ['taxonomy', 'parent_id', 'sort_order'],
                'content_categories_taxonomy_parent_sort_index'
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('content_categories') || ! Schema::hasColumn('content_categories', 'parent_id')) {
            return;
        }

        Schema::table('content_categories', function (Blueprint $table): void {
            $table->dropIndex('content_categories_taxonomy_parent_sort_index');
            $table->dropConstrainedForeignId('parent_id');
        });
    }
};
