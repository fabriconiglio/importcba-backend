<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('url', 500);
            $table->string('thumbnail_url', 500)->nullable();
            $table->string('small_url', 500)->nullable();
            $table->string('medium_url', 500)->nullable();
            $table->string('alt_text', 255)->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->index(['product_id', 'is_primary']);
            $table->index('sort_order');
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_images');
    }
};