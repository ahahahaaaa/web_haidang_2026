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
            if (! Schema::hasColumn('site_settings', 'google_recaptcha_v3_enabled')) {
                $table->boolean('google_recaptcha_v3_enabled')->default(false)->after('end_body_html');
            }

            if (! Schema::hasColumn('site_settings', 'google_recaptcha_v3_site_key')) {
                $table->string('google_recaptcha_v3_site_key')->nullable()->after('google_recaptcha_v3_enabled');
            }

            if (! Schema::hasColumn('site_settings', 'google_recaptcha_v3_secret_key')) {
                $table->text('google_recaptcha_v3_secret_key')->nullable()->after('google_recaptcha_v3_site_key');
            }

            if (! Schema::hasColumn('site_settings', 'google_recaptcha_v3_min_score')) {
                $table->decimal('google_recaptcha_v3_min_score', 3, 2)->default(0.50)->after('google_recaptcha_v3_secret_key');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table): void {
            $columns = array_values(array_filter([
                Schema::hasColumn('site_settings', 'google_recaptcha_v3_min_score') ? 'google_recaptcha_v3_min_score' : null,
                Schema::hasColumn('site_settings', 'google_recaptcha_v3_secret_key') ? 'google_recaptcha_v3_secret_key' : null,
                Schema::hasColumn('site_settings', 'google_recaptcha_v3_site_key') ? 'google_recaptcha_v3_site_key' : null,
                Schema::hasColumn('site_settings', 'google_recaptcha_v3_enabled') ? 'google_recaptcha_v3_enabled' : null,
            ]));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
