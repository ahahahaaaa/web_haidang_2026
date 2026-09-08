<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('seo_optimization_pages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('site_id', 80);
            $table->string('locale', 12)->default('vi');
            $table->string('page_type', 40)->index();
            $table->string('owner_type', 40);
            $table->string('owner_id', 100)->nullable();
            $table->string('route_name', 120)->nullable();
            $table->string('path', 700);
            $table->string('title', 500)->default('');
            $table->string('classification', 40)->index();
            $table->string('source_version', 100)->nullable();
            $table->string('source_hash', 64)->nullable();
            $table->json('capabilities')->nullable();
            $table->json('dependencies')->nullable();
            $table->json('keyword_brief')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->unique(['site_id', 'locale', 'owner_type', 'owner_id'], 'seo_opt_page_owner_unique');
            $table->timestamps();
        });

        Schema::create('seo_optimization_audits', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('page_id')->constrained('seo_optimization_pages')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('source_version', 100);
            $table->string('strategy_revision', 100);
            $table->string('rule_version', 60);
            $table->string('status', 30)->index();
            $table->decimal('score', 5, 1)->nullable();
            $table->string('grade', 20)->nullable();
            $table->json('snapshot');
            $table->json('report');
            $table->string('sheet_sync_status', 30)->default('pending');
            $table->index(['page_id', 'created_at']);
            $table->timestamps();
        });

        Schema::create('seo_optimization_tasks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('page_id')->constrained('seo_optimization_pages')->restrictOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('queued')->index();
            $table->json('brief');
            $table->json('snapshot');
            $table->string('source_version', 100);
            $table->string('strategy_revision', 100);
            $table->string('idempotency_key', 100)->unique();
            $table->string('request_hash', 64);
            $table->string('lease_token_hash', 64)->nullable();
            $table->string('leased_by', 100)->nullable();
            $table->timestamp('leased_until')->nullable()->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->index(['page_id', 'status']);
            $table->timestamps();
        });

        Schema::create('seo_optimization_proposals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('page_id')->constrained('seo_optimization_pages')->restrictOnDelete();
            $table->foreignUlid('task_id')->nullable()->constrained('seo_optimization_tasks')->restrictOnDelete();
            $table->foreignUlid('audit_id')->nullable()->constrained('seo_optimization_audits')->nullOnDelete();
            $table->string('source_version', 100);
            $table->string('strategy_revision', 100);
            $table->string('status', 30)->default('in_review')->index();
            $table->json('patch');
            $table->json('before');
            $table->json('qa');
            $table->json('claims')->nullable();
            $table->json('missing_facts')->nullable();
            $table->text('notes')->nullable();
            $table->string('idempotency_key', 100)->unique();
            $table->string('request_hash', 64);
            $table->string('content_hash', 64);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('applied_at')->nullable();
            $table->string('applied_version', 100)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->index(['page_id', 'created_at']);
            $table->timestamps();
        });

        Schema::create('seo_optimization_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('page_id')->nullable()->constrained('seo_optimization_pages')->restrictOnDelete();
            $table->foreignUlid('proposal_id')->nullable()->constrained('seo_optimization_proposals')->restrictOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event', 80)->index();
            $table->json('payload')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_optimization_credentials', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('name', 150);
            $table->string('token_hash', 64)->unique();
            $table->json('abilities');
            $table->json('allowed_page_types');
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('seo_optimization_outbox', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('event_key', 150)->unique();
            $table->string('destination', 60);
            $table->json('payload');
            $table->string('status', 30)->default('pending')->index();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_optimization_outbox');
        Schema::dropIfExists('seo_optimization_credentials');
        Schema::dropIfExists('seo_optimization_events');
        Schema::dropIfExists('seo_optimization_proposals');
        Schema::dropIfExists('seo_optimization_tasks');
        Schema::dropIfExists('seo_optimization_audits');
        Schema::dropIfExists('seo_optimization_pages');
    }
};
