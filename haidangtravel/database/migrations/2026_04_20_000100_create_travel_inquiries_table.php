<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('travel_inquiries', function (Blueprint $table): void {
            $table->id();
            $table->string('source')->index();
            $table->foreignId('tour_id')->nullable()->constrained('tours')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('context_title')->nullable();
            $table->string('status')->default('new')->index();
            $table->string('customer_name');
            $table->string('customer_phone');
            $table->string('customer_email')->nullable();
            $table->string('travel_date')->nullable();
            $table->unsignedInteger('party_size')->nullable();
            $table->text('message')->nullable();
            $table->string('page_url')->nullable();
            $table->string('mail_status')->default('pending');
            $table->string('mailed_to')->nullable();
            $table->timestamp('mailed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('travel_inquiries');
    }
};
