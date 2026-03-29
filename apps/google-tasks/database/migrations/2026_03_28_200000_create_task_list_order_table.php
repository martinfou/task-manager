<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_list_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('google_list_id');
            $table->unsignedInteger('position')->default(0);
            $table->boolean('pinned')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'google_list_id']);
            $table->index(['user_id', 'pinned', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_list_order');
    }
};
