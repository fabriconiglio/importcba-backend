<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->string('image_url', 500)->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            // $table->foreign('parent_id')->references('id')->on('categories')->onDelete('cascade');
            $table->index(['parent_id', 'is_active']);
            $table->index('slug');
        });
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
};
