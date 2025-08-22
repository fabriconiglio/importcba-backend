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
        Schema::table('brand_category', function (Blueprint $table) {
            // Agregar las columnas necesarias
            $table->uuid('brand_id');
            $table->uuid('category_id');
            $table->boolean('is_featured')->default(false); // Para destacar ciertas marcas en categorías
            $table->integer('sort_order')->default(0); // Para ordenar las marcas en cada categoría

            // Claves foráneas
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            
            // Índice único para evitar duplicados
            $table->unique(['brand_id', 'category_id']);
            
            // Índices para performance
            $table->index(['category_id', 'is_featured', 'sort_order']);
            $table->index('brand_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('brand_category', function (Blueprint $table) {
            // Eliminar índices y claves foráneas primero
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['category_id']);
            $table->dropUnique(['brand_id', 'category_id']);
            $table->dropIndex(['category_id', 'is_featured', 'sort_order']);
            $table->dropIndex(['brand_id']);
            
            // Eliminar las columnas
            $table->dropColumn(['brand_id', 'category_id', 'is_featured', 'sort_order']);
        });
    }
};
