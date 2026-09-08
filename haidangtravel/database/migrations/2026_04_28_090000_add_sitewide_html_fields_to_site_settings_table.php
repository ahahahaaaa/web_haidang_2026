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

        $afterHeaderColumn = Schema::hasColumn('site_settings', 'facebook_pixel_id')
            ? 'facebook_pixel_id'
            : 'seo_robots';

        Schema::table('site_settings', function (Blueprint $table) use ($afterHeaderColumn): void {
            if (! Schema::hasColumn('site_settings', 'after_header_html')) {
                $table->longText('after_header_html')->nullable()->after($afterHeaderColumn);
            }

            if (! Schema::hasColumn('site_settings', 'end_body_html')) {
                $table->longText('end_body_html')->nullable()->after('after_header_html');
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

            if (Schema::hasColumn('site_settings', 'end_body_html')) {
                $columns[] = 'end_body_html';
            }

            if (Schema::hasColumn('site_settings', 'after_header_html')) {
                $columns[] = 'after_header_html';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
