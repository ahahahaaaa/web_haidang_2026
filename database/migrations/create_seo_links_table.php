<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('seo_links', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_page_id');
            $table->unsignedBigInteger('target_page_id');
            $table->string('anchor_text')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('seo_links');
    }
};