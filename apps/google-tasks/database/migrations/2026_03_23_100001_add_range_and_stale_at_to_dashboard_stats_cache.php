<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dashboard_stats_cache', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
            $table->unsignedSmallInteger('range_days')->default(30)->after('user_id');
            $table->timestamp('stale_at')->nullable()->after('computed_at');
            $table->unique(['user_id', 'range_days']);
        });
    }

    public function down(): void
    {
        Schema::table('dashboard_stats_cache', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'range_days']);
            $table->dropColumn(['range_days', 'stale_at']);
            $table->unique('user_id');
        });
    }
};
