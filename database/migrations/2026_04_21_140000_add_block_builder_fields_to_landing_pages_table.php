<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->string('page_key')->nullable()->change();
            $table->string('template_key')->nullable()->after('page_key');
            $table->json('blocks')->nullable()->after('visual_config');
            $table->unique('slug');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'template_key',
                'blocks',
            ]);
            $table->string('page_key')->nullable(false)->change();
        });
    }
};
