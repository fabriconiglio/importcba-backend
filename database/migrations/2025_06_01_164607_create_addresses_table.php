<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('title', 100); // 'Casa', 'Trabajo', etc.
            $table->string('street_address', 255);
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('Argentina');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->index(['user_id', 'is_default']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('addresses');
    }
};

