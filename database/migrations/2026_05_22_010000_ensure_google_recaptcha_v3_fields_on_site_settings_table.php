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

        $this->addColumnIfMissing(
            'google_recaptcha_v3_enabled',
            fn (Blueprint $table): mixed => $table->boolean('google_recaptcha_v3_enabled')->default(false),
            'end_body_html',
        );

        $this->addColumnIfMissing(
            'google_recaptcha_v3_site_key',
            fn (Blueprint $table): mixed => $table->string('google_recaptcha_v3_site_key')->nullable(),
            'google_recaptcha_v3_enabled',
        );

        $this->addColumnIfMissing(
            'google_recaptcha_v3_secret_key',
            fn (Blueprint $table): mixed => $table->text('google_recaptcha_v3_secret_key')->nullable(),
            'google_recaptcha_v3_site_key',
        );

        $this->addColumnIfMissing(
            'google_recaptcha_v3_min_score',
            fn (Blueprint $table): mixed => $table->decimal('google_recaptcha_v3_min_score', 3, 2)->default(0.50),
            'google_recaptcha_v3_secret_key',
        );
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

    protected function addColumnIfMissing(string $column, \Closure $definition, ?string $after = null): void
    {
        if (Schema::hasColumn('site_settings', $column)) {
            return;
        }

        Schema::table('site_settings', function (Blueprint $table) use ($after, $definition): void {
            $columnDefinition = $definition($table);

            if ($after && Schema::hasColumn('site_settings', $after)) {
                $columnDefinition->after($after);
            }
        });
    }
};
