<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            if (! Schema::hasColumn('site_settings', 'tour_terms_title')) {
                $table->string('tour_terms_title')->nullable()->after('about_summary');
            }

            if (! Schema::hasColumn('site_settings', 'tour_terms_content')) {
                $table->longText('tour_terms_content')->nullable()->after('tour_terms_title');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            $columns = [];

            if (Schema::hasColumn('site_settings', 'tour_terms_content')) {
                $columns[] = 'tour_terms_content';
            }

            if (Schema::hasColumn('site_settings', 'tour_terms_title')) {
                $columns[] = 'tour_terms_title';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
