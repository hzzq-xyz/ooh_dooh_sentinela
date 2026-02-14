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
    Schema::create('validacoes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('inventario_id')->constrained('inventarios');
        $table->string('file_path');
        $table->string('hash')->unique(); // O "código" do link (ex: nela.xyz/v/abc123)
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('validacaos');
    }
    
    
};

