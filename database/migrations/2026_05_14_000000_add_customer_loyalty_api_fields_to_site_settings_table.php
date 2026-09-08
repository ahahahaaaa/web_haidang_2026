<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->string('customer_loyalty_api_base_url')->nullable()->after('structured_data');
            $table->string('customer_loyalty_api_username')->nullable()->after('customer_loyalty_api_base_url');
            $table->text('customer_loyalty_api_password')->nullable()->after('customer_loyalty_api_username');
            $table->text('customer_loyalty_api_token')->nullable()->after('customer_loyalty_api_password');
            $table->timestamp('customer_loyalty_api_token_expires_at')->nullable()->after('customer_loyalty_api_token');
            $table->timestamp('customer_loyalty_api_token_refreshed_at')->nullable()->after('customer_loyalty_api_token_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'customer_loyalty_api_base_url',
                'customer_loyalty_api_username',
                'customer_loyalty_api_password',
                'customer_loyalty_api_token',
                'customer_loyalty_api_token_expires_at',
                'customer_loyalty_api_token_refreshed_at',
            ]);
        });
    }
};
