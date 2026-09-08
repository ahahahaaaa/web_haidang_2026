<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('logo_url')->nullable()->after('active_theme');
            $table->string('favicon_url')->nullable()->after('logo_url');
            $table->string('og_image_url')->nullable()->after('favicon_url');
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->string('cover_image_url')->nullable()->after('cover_alt');
        });

        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->string('cover_image_url')->nullable()->after('cover_alt');
        });
    }

    public function down(): void
    {
        Schema::table('blog_posts', function (Blueprint $table): void {
            $table->dropColumn('cover_image_url');
        });

        Schema::table('services', function (Blueprint $table): void {
            $table->dropColumn('cover_image_url');
        });

        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn(['logo_url', 'favicon_url', 'og_image_url']);
        });
    }
};
