<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_campaigns', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('landing_page_id')->nullable()->constrained('landing_pages')->nullOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('frame_image_url')->nullable();
            $table->string('code_prefix')->nullable();
            $table->unsignedInteger('code_quantity')->default(0);
            $table->string('code_set_version', 64)->index();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('meta')->nullable();
            $table->timestamp('codes_refreshed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('voucher_codes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('voucher_campaign_id')->constrained('voucher_campaigns')->cascadeOnDelete();
            $table->foreignId('travel_inquiry_id')->nullable()->constrained('travel_inquiries')->nullOnDelete();
            $table->string('code');
            $table->string('status')->default('available')->index();
            $table->string('cookie_token', 80)->nullable()->index();
            $table->string('customer_phone_hash', 64)->nullable()->index();
            $table->timestamp('claimed_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['voucher_campaign_id', 'code']);
            $table->index(['voucher_campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_codes');
        Schema::dropIfExists('voucher_campaigns');
    }
};
