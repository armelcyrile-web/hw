<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table): void {
            $table->id();
            $table->string('titre');
            $table->text('description');
            $table->enum('statut', ['nouveau', 'assigne', 'resolu'])->default('nouveau');
            $table->enum('origine', ['manuel', 'automatique']);
            $table->enum('priorite', ['basse', 'normale', 'urgente']);
            $table->foreignId('site_id')->constrained('sites')->onDelete('cascade');
            $table->foreignId('technicien_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('date_resolution')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
