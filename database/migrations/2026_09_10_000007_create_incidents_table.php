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
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('folio', 30)->unique();
            $table->string('title', 255);
            $table->text('description');
            $table->string('location', 255);
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('priority_id')->constrained('priorities')->restrictOnDelete();
            $table->foreignId('status_id')->constrained('statuses')->restrictOnDelete();
            $table->foreignId('reported_by_user_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('solution_description')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('reported_at')->useCurrent();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            // Índices para optimizar consultas de telemetría y dashboard
            $table->index(['status_id', 'priority_id', 'created_at']);
            $table->index(['reported_by_user_id', 'created_at']);
            $table->index(['assigned_to_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
