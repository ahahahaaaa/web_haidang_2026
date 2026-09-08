<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('voucher_campaigns')
            ->whereNull('code_valid_until')
            ->whereNotNull('ends_at')
            ->update(['code_valid_until' => DB::raw('ends_at')]);
    }

    public function down(): void
    {
        DB::table('voucher_campaigns')
            ->whereNotNull('code_valid_until')
            ->whereColumn('code_valid_until', 'ends_at')
            ->update(['code_valid_until' => null]);
    }
};
