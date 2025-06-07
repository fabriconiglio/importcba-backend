<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 100);
            $table->enum('type', ['credit_card', 'debit_card', 'bank_transfer', 'cash_on_delivery', 'mercadopago', 'paypal']);
            $table->boolean('is_active')->default(true);
            $table->json('configuration')->nullable(); // Para configuraciones específicas
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('payment_methods');
    }
};
