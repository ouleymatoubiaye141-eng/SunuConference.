<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conferences', function (Blueprint $table): void {
            $table->dateTime('submission_deadline')->change();
            $table->dateTime('review_deadline')->change();
            $table->dateTime('camera_ready_deadline')->change();
        });
    }

    public function down(): void
    {
        Schema::table('conferences', function (Blueprint $table): void {
            $table->date('submission_deadline')->change();
            $table->date('review_deadline')->change();
            $table->date('camera_ready_deadline')->change();
        });
    }
};
