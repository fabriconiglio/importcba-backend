<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Eliminar la restricción de clave foránea existente
            $table->dropForeign(['product_id']);
            
            // Recrear la restricción con ON DELETE SET NULL
            $table->foreign('product_id')->references('id')->on('products')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('order_items', function (Blueprint $table) {
            // Eliminar la nueva restricción
            $table->dropForeign(['product_id']);
            
            // Restaurar la restricción original sin ON DELETE
            $table->foreign('product_id')->references('id')->on('products');
        });
    }
};
