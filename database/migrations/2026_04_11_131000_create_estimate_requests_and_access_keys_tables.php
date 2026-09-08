<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimate_access_keys', function (Blueprint $table) {
            $table->id();
            $table->string('label');
            $table->string('code')->unique();
            $table->json('tier_codes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('estimate_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estimate_access_key_id')->nullable()->constrained('estimate_access_keys')->nullOnDelete();
            $table->string('tier_code')->index();
            $table->string('tier_name');
            $table->boolean('requires_key')->default(false);
            $table->string('estimate_key_code')->nullable();
            $table->string('delivery_channel')->default('collection')->index();
            $table->string('mail_status')->default('pending')->index();
            $table->string('status')->default('submitted')->index();
            $table->string('customer_name');
            $table->string('customer_phone', 50);
            $table->string('customer_email');
            $table->string('company_name')->nullable();
            $table->string('project_location')->nullable();
            $table->text('project_overview')->nullable();
            $table->string('page_url')->nullable();
            $table->string('mailed_to')->nullable();
            $table->timestamp('mailed_at')->nullable();
            $table->json('input_payload')->nullable();
            $table->json('result_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_requests');
        Schema::dropIfExists('estimate_access_keys');
    }
};
