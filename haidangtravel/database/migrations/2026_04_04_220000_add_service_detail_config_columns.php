<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->json('detail_config')->nullable()->after('schema');
        });

        Schema::table('landing_pages', function (Blueprint $table) {
            $table->json('service_detail_config')->nullable()->after('schema');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('service_detail_config');
        });

        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('detail_config');
        });
    }
};
