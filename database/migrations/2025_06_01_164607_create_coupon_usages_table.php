<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('coupon_id');
            $table->uuid('user_id');
            $table->uuid('order_id');
            $table->decimal('discount_amount', 10, 2);
            $table->timestamp('used_at')->useCurrent();
            
            $table->foreign('coupon_id')->references('id')->on('coupons')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('cascade');
            
            $table->unique(['coupon_id', 'user_id', 'order_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('coupon_usages');
    }
};
