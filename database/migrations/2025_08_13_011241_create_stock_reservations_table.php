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
        Schema::create('stock_reservations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('order_id')->nullable();
            $table->uuid('user_id')->nullable();
            $table->string('session_id')->nullable();
            $table->integer('quantity');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'expired']);
            $table->timestamp('expires_at');
            $table->json('metadata')->nullable(); // Para información adicional
            $table->timestamps();
            
            // Índices
            $table->index(['product_id', 'status']);
            $table->index(['order_id']);
            $table->index(['user_id']);
            $table->index(['session_id']);
            $table->index(['expires_at']);
            $table->index(['status', 'expires_at']);
            
            // Foreign keys
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_reservations');
    }
};
