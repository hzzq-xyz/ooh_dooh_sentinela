<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('renovacaos', function (Blueprint $table) {
            $table->id();
            
            // Dados do Contrato
            $table->boolean('ativo')->default(true);
            $table->string('cliente');
            $table->string('comercial');
            $table->string('canal_selecionado')->nullable();
            $table->text('endereco_manual')->nullable();
            
            // Datas de Vigência do Contrato
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            
            // Regra de Datas (O famoso 15 e 20)
            $table->integer('dia_padrao_captacao')->default(15);
            $table->integer('dia_padrao_entrega')->default(20);

            // Obs que se repetem
            $table->text('obs_midia')->nullable();
            $table->text('obs_captacao')->nullable();
            
            // Quem faz
            $table->string('origem')->default('FOTÓGRAFO'); // TI, FOTÓGRAFO, TERCEIROS

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('renovacaos');
    }
};