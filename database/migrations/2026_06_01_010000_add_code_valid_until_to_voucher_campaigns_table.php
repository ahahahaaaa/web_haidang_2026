<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_campaigns', function (Blueprint $table): void {
            $table->timestamp('code_valid_until')->nullable()->after('ends_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('voucher_campaigns', function (Blueprint $table): void {
            $table->dropColumn('code_valid_until');
        });
    }
};
