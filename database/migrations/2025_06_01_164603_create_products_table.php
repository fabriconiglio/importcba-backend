<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('sku', 100)->unique();
            $table->text('description')->nullable();
            $table->string('short_description', 500)->nullable();
            $table->uuid('category_id')->nullable();
            $table->uuid('brand_id')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('sale_price', 10, 2)->nullable();
            $table->decimal('cost_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->integer('min_stock_level')->default(5);
            $table->decimal('weight', 8, 3)->nullable();
            $table->string('dimensions', 100)->nullable(); // ej: "10x15x20 cm"
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();
            
            $table->foreign('category_id')->references('id')->on('categories');
            $table->foreign('brand_id')->references('id')->on('brands');
            
            // Índices para optimización
            $table->index(['category_id', 'is_active']);
            $table->index(['brand_id', 'is_active']);
            $table->index(['is_featured', 'is_active']);
            $table->index('sku');
            $table->index('stock_quantity');
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};
