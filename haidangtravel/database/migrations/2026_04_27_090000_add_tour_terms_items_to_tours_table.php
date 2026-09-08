<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('tours') || Schema::hasColumn('tours', 'tour_terms_items')) {
            return;
        }

        $afterColumn = Schema::hasColumn('tours', 'faq_items') ? 'faq_items' : 'inclusions';

        Schema::table('tours', function (Blueprint $table) use ($afterColumn): void {
            $table->json('tour_terms_items')->nullable()->after($afterColumn);
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('tours') || ! Schema::hasColumn('tours', 'tour_terms_items')) {
            return;
        }

        Schema::table('tours', function (Blueprint $table): void {
            $table->dropColumn('tour_terms_items');
        });
    }
};
