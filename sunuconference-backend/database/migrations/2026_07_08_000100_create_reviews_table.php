<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('reviewer_submission_id')->constrained('reviewer_submission')->cascadeOnDelete();
            $table->unsignedTinyInteger('originality_score')->nullable();
            $table->unsignedTinyInteger('relevance_score')->nullable();
            $table->unsignedTinyInteger('presentation_score')->nullable();
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->text('public_comment')->nullable();
            $table->text('private_comment')->nullable();
            $table->string('recommendation')->nullable();
            $table->boolean('conflict_of_interest')->default(false);
            $table->text('conflict_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique('reviewer_submission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
