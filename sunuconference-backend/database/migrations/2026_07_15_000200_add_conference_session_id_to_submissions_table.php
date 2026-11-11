<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submissions', function (Blueprint $table): void {
            $table->foreignId('conference_session_id')
                ->nullable()
                ->after('topic_id')
                ->constrained('conference_sessions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('submissions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('conference_session_id');
        });
    }
};
