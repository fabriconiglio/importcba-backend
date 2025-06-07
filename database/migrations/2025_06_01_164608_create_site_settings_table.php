<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('site_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->enum('type', ['text', 'number', 'boolean', 'json'])->default('text');
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('key');
        });
    }

    public function down()
    {
        Schema::dropIfExists('site_settings');
    }
};
