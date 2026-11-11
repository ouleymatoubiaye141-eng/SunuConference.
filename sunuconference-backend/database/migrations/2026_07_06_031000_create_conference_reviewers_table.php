<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conference_reviewers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('conference_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('invited_at')->nullable();
            $table->timestamps();

            $table->unique(['conference_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conference_reviewers');
    }
};
