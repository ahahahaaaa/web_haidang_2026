<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->json('gallery')->nullable()->after('detail_config');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->json('gallery')->nullable()->after('schema');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn('gallery');
        });

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('gallery');
        });
    }
};
