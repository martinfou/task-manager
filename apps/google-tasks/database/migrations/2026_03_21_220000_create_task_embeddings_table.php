<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_embeddings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('task_list_id');
            $table->string('task_id');
            $table->string('content_hash', 64);
            $table->unsignedSmallInteger('dimensions');
            $table->json('embedding');
            $table->string('task_list_title');
            $table->text('title_raw');
            $table->text('notes_raw')->nullable();
            $table->string('status', 32)->default('needsAction');
            $table->timestamps();

            $table->unique(['user_id', 'task_list_id', 'task_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_embeddings');
    }
};
