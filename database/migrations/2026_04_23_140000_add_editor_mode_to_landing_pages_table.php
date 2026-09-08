<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->string('editor_mode')->default('blocks')->after('template_key');
        });

        DB::table('landing_pages')
            ->whereNull('editor_mode')
            ->update(['editor_mode' => 'blocks']);
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('editor_mode');
        });
    }
};
