<?php

use App\Models\Conference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chair_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('acronym');
            $table->text('description')->nullable();
            $table->text('call_for_papers')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->date('submission_deadline');
            $table->date('review_deadline');
            $table->date('camera_ready_deadline');
            $table->string('status')->default(Conference::STATUS_PENDING);
            $table->text('rejection_reason')->nullable();
            $table->boolean('program_published')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conferences');
    }
};
