<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('veiculacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventario_id')->constrained('inventarios')->onDelete('cascade');
            $table->string('cliente');
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->integer('slots')->default(1);
            $table->enum('tipo_acordo', ['PAGO', 'CORTESIA', 'PERMUTA', 'INTERNO'])->default('PAGO');
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('veiculacoes');
    }
};