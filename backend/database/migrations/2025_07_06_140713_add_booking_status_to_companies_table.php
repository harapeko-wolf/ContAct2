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
        Schema::table('companies', function (Blueprint $table) {
            $table->enum('booking_status', ['considering', 'confirmed', 'cancelled'])
                  ->default('considering')
                  ->after('status')
                  ->comment('予約ステータス: considering=検討中, confirmed=確定, cancelled=キャンセル');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('booking_status');
        });
    }
};
