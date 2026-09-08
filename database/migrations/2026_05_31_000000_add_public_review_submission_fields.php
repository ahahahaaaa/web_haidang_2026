<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tours')) {
            Schema::table('tours', function (Blueprint $table): void {
                if (! Schema::hasColumn('tours', 'review_submission_enabled')) {
                    $table->boolean('review_submission_enabled')->default(false)->after('rating_count')->index();
                }

                if (! Schema::hasColumn('tours', 'review_submission_password')) {
                    $table->text('review_submission_password')->nullable()->after('review_submission_enabled');
                }

                if (! Schema::hasColumn('tours', 'review_submission_token')) {
                    $table->string('review_submission_token', 64)->nullable()->unique()->after('review_submission_password');
                }
            });
        }

        if (Schema::hasTable('travel_reviews')) {
            Schema::table('travel_reviews', function (Blueprint $table): void {
                if (! Schema::hasColumn('travel_reviews', 'author_email')) {
                    $table->string('author_email')->nullable()->after('author_title');
                }

                if (! Schema::hasColumn('travel_reviews', 'author_phone')) {
                    $table->string('author_phone', 50)->nullable()->after('author_email');
                }

                if (! Schema::hasColumn('travel_reviews', 'source')) {
                    $table->string('source', 40)->default('admin')->after('status')->index();
                }

                if (! Schema::hasColumn('travel_reviews', 'submitted_at')) {
                    $table->timestamp('submitted_at')->nullable()->after('published_at')->index();
                }

                if (! Schema::hasColumn('travel_reviews', 'metadata')) {
                    $table->json('metadata')->nullable()->after('submitted_at');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('travel_reviews')) {
            $columns = collect([
                'author_email',
                'author_phone',
                'source',
                'submitted_at',
                'metadata',
            ])->filter(fn (string $column): bool => Schema::hasColumn('travel_reviews', $column))->all();

            if ($columns !== []) {
                Schema::table('travel_reviews', function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }

        if (Schema::hasTable('tours')) {
            $columns = collect([
                'review_submission_enabled',
                'review_submission_password',
                'review_submission_token',
            ])->filter(fn (string $column): bool => Schema::hasColumn('tours', $column))->all();

            if ($columns !== []) {
                Schema::table('tours', function (Blueprint $table) use ($columns): void {
                    $table->dropColumn($columns);
                });
            }
        }
    }
};
