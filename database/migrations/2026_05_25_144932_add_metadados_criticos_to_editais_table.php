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
            $table->text('documentacoes_exigidas')->nullable();
            $table->boolean('exige_ict')->default(false); // Flag rápida para o painel lateral
            $table->string('valor_minimo_proposta')->nullable();
            $table->date('data_abertura_inscricoes')->nullable();
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
