<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('services', 'faq_items') || ! Schema::hasColumn('services', 'related_questions')) {
            Schema::table('services', function (Blueprint $table) {
                if (! Schema::hasColumn('services', 'faq_items')) {
                    $table->json('faq_items')->nullable();
                }

                if (! Schema::hasColumn('services', 'related_questions')) {
                    $table->json('related_questions')->nullable();
                }
            });
        }

        if (! Schema::hasColumn('projects', 'faq_items') || ! Schema::hasColumn('projects', 'related_questions')) {
            Schema::table('projects', function (Blueprint $table) {
                if (! Schema::hasColumn('projects', 'faq_items')) {
                    $table->json('faq_items')->nullable();
                }

                if (! Schema::hasColumn('projects', 'related_questions')) {
                    $table->json('related_questions')->nullable();
                }
            });
        }

        if (! Schema::hasColumn('landing_pages', 'faq_items')) {
            Schema::table('landing_pages', function (Blueprint $table) {
                $table->json('faq_items')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('services', 'faq_items') || Schema::hasColumn('services', 'related_questions')) {
            Schema::table('services', function (Blueprint $table) {
                $columns = [];

                if (Schema::hasColumn('services', 'faq_items')) {
                    $columns[] = 'faq_items';
                }

                if (Schema::hasColumn('services', 'related_questions')) {
                    $columns[] = 'related_questions';
                }

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasColumn('projects', 'faq_items') || Schema::hasColumn('projects', 'related_questions')) {
            Schema::table('projects', function (Blueprint $table) {
                $columns = [];

                if (Schema::hasColumn('projects', 'faq_items')) {
                    $columns[] = 'faq_items';
                }

                if (Schema::hasColumn('projects', 'related_questions')) {
                    $columns[] = 'related_questions';
                }

                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasColumn('landing_pages', 'faq_items')) {
            Schema::table('landing_pages', function (Blueprint $table) {
                $table->dropColumn('faq_items');
            });
        }
    }
};
