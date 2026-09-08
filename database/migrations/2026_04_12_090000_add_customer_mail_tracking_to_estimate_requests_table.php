<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('estimate_requests', function (Blueprint $table) {
            $table->string('customer_mail_status')->default('skipped')->index()->after('mail_status');
            $table->string('customer_mailed_to')->nullable()->after('mailed_to');
            $table->timestamp('customer_mailed_at')->nullable()->after('mailed_at');
        });
    }

    public function down(): void
    {
        Schema::table('estimate_requests', function (Blueprint $table) {
            $table->dropColumn([
                'customer_mail_status',
                'customer_mailed_to',
                'customer_mailed_at',
            ]);
        });
    }
};
