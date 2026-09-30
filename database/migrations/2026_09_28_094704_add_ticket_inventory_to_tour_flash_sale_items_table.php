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
        Schema::table('tour_flash_sale_items', function (Blueprint $table): void {
            $table->unsignedInteger('ticket_quantity')->nullable()->after('flash_price');
            $table->unsignedInteger('booked_quantity')->default(0)->after('ticket_quantity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tour_flash_sale_items', function (Blueprint $table): void {
            $table->dropColumn(['ticket_quantity', 'booked_quantity']);
        });
    }
};
