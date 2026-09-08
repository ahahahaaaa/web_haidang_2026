<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 50)->nullable()->after('email');
        });

        Schema::table('tours', function (Blueprint $table): void {
            $table->foreignId('managed_by_user_id')
                ->nullable()
                ->after('scope')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('contact_phone', 50)->nullable()->after('departure_location');
        });
    }

    public function down(): void
    {
        Schema::table('tours', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('managed_by_user_id');
            $table->dropColumn('contact_phone');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('phone');
        });
    }
};
