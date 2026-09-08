<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voucher_codes', function (Blueprint $table): void {
            $table->string('code_set_version', 64)->nullable()->after('code')->index();
            $table->foreignId('used_by')->nullable()->after('claimed_at')->constrained('users')->nullOnDelete();
            $table->timestamp('used_at')->nullable()->after('used_by')->index();
            $table->text('used_note')->nullable()->after('used_at');
            $table->index(['voucher_campaign_id', 'code_set_version', 'status'], 'voucher_codes_campaign_version_status_index');
            $table->index(['voucher_campaign_id', 'code_set_version', 'customer_phone_hash'], 'voucher_codes_campaign_version_phone_index');
        });

        DB::table('voucher_codes')
            ->whereNull('code_set_version')
            ->orderBy('id')
            ->chunkById(500, function ($codes): void {
                $campaignVersions = DB::table('voucher_campaigns')
                    ->whereIn('id', $codes->pluck('voucher_campaign_id')->unique()->values())
                    ->pluck('code_set_version', 'id');

                foreach ($codes as $code) {
                    DB::table('voucher_codes')
                        ->where('id', $code->id)
                        ->update([
                            'code_set_version' => $campaignVersions[$code->voucher_campaign_id] ?? null,
                        ]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('voucher_codes', function (Blueprint $table): void {
            $table->dropIndex('voucher_codes_campaign_version_status_index');
            $table->dropIndex('voucher_codes_campaign_version_phone_index');
            $table->dropConstrainedForeignId('used_by');
            $table->dropColumn([
                'code_set_version',
                'used_at',
                'used_note',
            ]);
        });
    }
};
