<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estimate_component_nodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('estimate_component_nodes')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('node_type')->default('group')->index();
            $table->json('building_types')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('estimate_price_books', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('version')->default('v1');
            $table->string('status')->default('draft')->index();
            $table->json('level_support')->nullable();
            $table->json('building_types')->nullable();
            $table->json('item_prices')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('estimate_catalog_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('component_node_id')->constrained('estimate_component_nodes')->cascadeOnDelete();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('item_type')->default('addon')->index();
            $table->string('unit')->default('item');
            $table->string('pricing_mode')->default('fixed')->index();
            $table->text('default_quantity_formula')->nullable();
            $table->json('level_support')->nullable();
            $table->json('building_types')->nullable();
            $table->string('price_book_code')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('estimate_room_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('room_type')->index();
            $table->json('building_types')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('estimate_room_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_template_id')->constrained('estimate_room_templates')->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->constrained('estimate_catalog_items')->cascadeOnDelete();
            $table->text('default_quantity_formula')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['room_template_id', 'catalog_item_id'], 'estimate_room_template_catalog_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estimate_room_template_items');
        Schema::dropIfExists('estimate_room_templates');
        Schema::dropIfExists('estimate_catalog_items');
        Schema::dropIfExists('estimate_price_books');
        Schema::dropIfExists('estimate_component_nodes');
    }
};
