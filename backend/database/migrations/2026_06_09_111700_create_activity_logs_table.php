<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');           // e.g. "project.created"
            $table->string('subject_type')->nullable();   // e.g. "Project"
            $table->unsignedBigInteger('subject_id')->nullable();  // e.g. 42
            $table->json('metadata')->nullable();          // extra context
            $table->timestamps();
 
            $table->index(['subject_type', 'subject_id']);
            $table->index('action');
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
