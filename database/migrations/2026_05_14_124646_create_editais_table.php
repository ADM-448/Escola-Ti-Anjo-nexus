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
        Schema::create('editais', function (Blueprint $table) {
        $table->id();
        $table->string('external_id')->unique();
        $table->string('titulo');
        $table->string('link');
        $table->text('objetivo')->nullable();
        $table->string('data_publicacao')->nullable();
        $table->text('condicao_financiamento')->nullable();
        $table->string('operacao')->nullable();
        $table->string('publico')->nullable();
        $table->string('fonte')->default('FINEP');
        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('editais');
    }
};
