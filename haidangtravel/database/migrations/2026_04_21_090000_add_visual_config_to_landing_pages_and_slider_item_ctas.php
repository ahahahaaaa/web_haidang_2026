<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('slug');
            $table->json('visual_config')->nullable()->after('home_config');
        });

        Schema::table('slider_items', function (Blueprint $table) {
            $table->string('primary_label')->nullable()->after('cta_url');
            $table->string('primary_url')->nullable()->after('primary_label');
            $table->string('secondary_label')->nullable()->after('primary_url');
            $table->string('secondary_url')->nullable()->after('secondary_label');
            $table->string('video_url')->nullable()->after('secondary_url');
        });
    }

    public function down(): void
    {
        Schema::table('slider_items', function (Blueprint $table) {
            $table->dropColumn([
                'primary_label',
                'primary_url',
                'secondary_label',
                'secondary_url',
                'video_url',
            ]);
        });

        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'visual_config',
            ]);
        });
    }
};
