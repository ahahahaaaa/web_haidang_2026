<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('destinations')) {
            return;
        }

        if (! Schema::hasColumn('destinations', 'show_tours_on_page')) {
            Schema::table('destinations', function (Blueprint $table): void {
                $table->boolean('show_tours_on_page')
                    ->default(true)
                    ->after('is_country_root');
            });
        }

        if (! Schema::hasColumn('destinations', 'show_blogs_on_page')) {
            Schema::table('destinations', function (Blueprint $table): void {
                $table->boolean('show_blogs_on_page')
                    ->default(false)
                    ->after('show_tours_on_page');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('destinations')) {
            return;
        }

        Schema::table('destinations', function (Blueprint $table): void {
            if (Schema::hasColumn('destinations', 'show_blogs_on_page')) {
                $table->dropColumn('show_blogs_on_page');
            }

            if (Schema::hasColumn('destinations', 'show_tours_on_page')) {
                $table->dropColumn('show_tours_on_page');
            }
        });
    }
};
