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
            if (! Schema::hasColumn('site_settings', 'ga_measurement_id')) {
                $table->string('ga_measurement_id')->nullable()->after('seo_robots');
            }

            if (! Schema::hasColumn('site_settings', 'facebook_pixel_id')) {
                $table->string('facebook_pixel_id')->nullable()->after('ga_measurement_id');
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

            if (Schema::hasColumn('site_settings', 'facebook_pixel_id')) {
                $columns[] = 'facebook_pixel_id';
            }

            if (Schema::hasColumn('site_settings', 'ga_measurement_id')) {
                $columns[] = 'ga_measurement_id';
            }

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
