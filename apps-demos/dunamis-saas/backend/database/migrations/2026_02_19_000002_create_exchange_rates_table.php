<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->string('moneda', 10)->default('USD');
            $table->string('nombre', 50);
            $table->decimal('compra', 14, 4)->default(0);
            $table->decimal('venta', 14, 4)->default(0);
            $table->timestamp('fecha_actualizacion')->nullable();
            $table->timestamps();
            $table->unique(['moneda', 'nombre']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
    }
};
