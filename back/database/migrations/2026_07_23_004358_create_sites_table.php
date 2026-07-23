<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table): void {
            $table->id();
            $table->string('nom');
            $table->string('url');
            $table->enum('statut_disponibilite', ['en_ligne', 'hors_ligne', 'inconnu'])->default('inconnu');
            $table->timestamp('date_derniere_verification')->nullable();
            $table->foreignId('client_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
