<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_view_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('view_name', 20); // today, inbox, all
            $table->string('params_hash', 64)->default(''); // hash of query params (showCompleted, etc.)
            $table->json('payload'); // serialized response payload
            $table->timestamp('computed_at');
            $table->timestamp('stale_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'view_name', 'params_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_view_cache');
    }
};
