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
        Schema::create('seo_optimization_policies', function (Blueprint $table) {
            $table->id();
            $table->string('site_id', 80)->unique();
            $table->string('publish_mode', 30)->default('preview');
            $table->json('allowed_page_types')->nullable();
            $table->string('revision', 36);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seo_optimization_policies');
    }
};
