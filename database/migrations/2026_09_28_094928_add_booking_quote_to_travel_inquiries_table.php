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
        Schema::table('travel_inquiries', function (Blueprint $table): void {
            $table->foreignId('tour_departure_id')->nullable()->after('tour_id')->constrained('tour_departures')->nullOnDelete();
            $table->foreignId('tour_flash_sale_item_id')->nullable()->after('tour_departure_id')->constrained('tour_flash_sale_items')->nullOnDelete();
            $table->unsignedBigInteger('quoted_unit_price')->nullable()->after('party_size');
            $table->unsignedBigInteger('regular_unit_price')->nullable()->after('quoted_unit_price');
            $table->string('price_type', 30)->nullable()->after('regular_unit_price')->index();
            $table->unsignedInteger('ticket_count')->nullable()->after('price_type');
            $table->timestamp('quoted_at')->nullable()->after('ticket_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('travel_inquiries', function (Blueprint $table): void {
            $table->dropForeign(['tour_departure_id']);
            $table->dropForeign(['tour_flash_sale_item_id']);
            $table->dropIndex(['price_type']);
            $table->dropColumn([
                'tour_departure_id',
                'tour_flash_sale_item_id',
                'quoted_unit_price',
                'regular_unit_price',
                'price_type',
                'ticket_count',
                'quoted_at',
            ]);
        });
    }
};
