<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('content_clusters', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('primary_keyword');
            $table->json('secondary_keywords')->nullable();
            $table->json('lsi_keywords')->nullable();
            $table->string('intent')->nullable();
            $table->string('target_page_type')->nullable();
            $table->string('status')->default('draft');
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('content_clusters');
    }
};