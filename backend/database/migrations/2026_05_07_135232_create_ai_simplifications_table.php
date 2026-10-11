<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_simplifications', function (Blueprint $table) {
            $table->id();

            $table->foreignId('feedback_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->text('simplified_feedback');

            $table->enum('sentiment', [
                'positive',
                'neutral',
                'negative'
            ]);

            $table->enum('priority', [
                'low',
                'medium',
                'high'
            ]);

            $table->text('suggested_action');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_simplifications');
    }
};