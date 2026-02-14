<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pautas', function (Blueprint $table) {
            $table->id();
            $table->date('data_insercao')->nullable();
            $table->string('pi')->nullable();
            $table->string('cliente')->nullable();
            $table->string('origem')->default('FOTÓGRAFO'); // TI, FOTÓGRAFO, TERCEIROS
            $table->string('canal_selecionado')->nullable(); // Para filtro
            
            // Relacionamento (pode ser múltiplo, mas deixamos um principal opcional)
            $table->foreignId('inventario_id')->nullable()->constrained('inventarios')->nullOnDelete();
            
            $table->text('endereco_manual')->nullable();
            $table->string('comercial')->nullable();
            $table->text('obs_midia')->nullable();
            
            // Prazos
            $table->date('prazo_captacao')->nullable();
            $table->date('prazo_envio')->nullable();
            $table->date('data_captacao')->nullable(); // Data real que foi feito
            $table->date('data_envio_real')->nullable(); // Data do checking enviado
            
            $table->string('status')->default('CAPTAÇÃO'); // CAPTAÇÃO, MONTAGEM, ENVIADO
            $table->text('obs_captacao')->nullable();
            $table->text('link_drive')->nullable();
            $table->string('motivo_atraso')->nullable();
            
            $table->timestamps();
        });

        // Tabela Pivô para ligar 1 Pauta a Vários Painéis (Inventários)
        Schema::create('inventario_pauta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pauta_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventario_id')->constrained()->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_pauta');
        Schema::dropIfExists('pautas');
    }
};