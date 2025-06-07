<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attribute_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('attribute_id');
            $table->string('value', 255);
            $table->string('color_code', 7)->nullable(); // Para colores hex
            $table->timestamps();
            
            $table->foreign('attribute_id')->references('id')->on('attributes')->onDelete('cascade');
            $table->unique(['attribute_id', 'value']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('attribute_values');
    }
};
