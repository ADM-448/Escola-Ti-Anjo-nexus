<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('editais', function (Blueprint $table) {
            $table->string('valor_total')->nullable(); // Ex: R$ 50.000.000,00
            $table->string('valor_maximo_proposta')->nullable(); // Ex: R$ 1.500.000,00
            $table->date('prazo_submissao')->nullable();
            $table->date('resultado_preliminar')->nullable();
            $table->date('resultado_final')->nullable();
            $table->text('elegibilidade_detalhada')->nullable(); // Regras específicas de quem pode se inscrever
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('editais', function (Blueprint $table) {
            //
        });
    }
};
