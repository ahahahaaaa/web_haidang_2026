<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('slider_items', function (Blueprint $table) {
            $table->boolean('show_overlay')->default(true)->after('video_url');
            $table->boolean('show_inner_media')->default(true)->after('show_overlay');
        });
    }

    public function down(): void
    {
        Schema::table('slider_items', function (Blueprint $table) {
            $table->dropColumn([
                'show_overlay',
                'show_inner_media',
            ]);
        });
    }
};
