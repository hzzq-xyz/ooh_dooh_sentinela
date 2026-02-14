<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verifica se a tabela já existe antes de criar para evitar erros
        if (!Schema::hasTable('inventarios')) {
            Schema::create('inventarios', function (Blueprint $table) {
                $table->id();
                $table->string('tipo')->default('On');
                $table->string('codigo')->nullable();
                $table->string('canal');
                $table->integer('impactos')->nullable();
                $table->integer('qtd_slots')->default(6);
                $table->string('cidade')->default('Porto Alegre');
                $table->string('bairro')->nullable();
                $table->string('endereco');
                $table->string('coordenadas_gps')->nullable();
                $table->boolean('iluminado')->default(false);
                
                // Campos específicos
                $table->integer('largura_px')->nullable();
                $table->integer('altura_px')->nullable();
                $table->string('sistema')->nullable();
                $table->integer('tempo_maximo')->nullable();
                $table->string('mockup_image')->nullable();
                $table->text('mockup_css')->nullable();
                $table->string('dimensoes_fisicas')->nullable();
                
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('inventarios');
    }
};