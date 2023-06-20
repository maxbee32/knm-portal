<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('mode_of_payment');
            $table->string('cardNumber')->nullable();
            $table->string('phone_number');
            $table->string('cvc')->nullable();
            $table->float('amount');
            $table->string('currency_type');
            // $table->string('email');
            $table->string('exp_month')->nullable();
            $table->string('exp_year')->nullable();
            $table->timestamps();



            $table->foreign('user_id')
            ->references('id')->on('users')
            ->onDelete('cascade')->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
};
