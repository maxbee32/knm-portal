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
        Schema::create('etickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticketid');
            $table->integer('number_of_ticket');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('fullname');
            $table->string('phone_number');
            $table->string('digital_address')->nullable();
            $table->dateTime('reservation_date');
            $table->string('children_visitor_category')->nullable();
            $table->integer('number_of_children')->default(0);
            $table->string('adult_visitor_category')->nullable();
            $table->integer('number_of_adult')->default(0);
            $table->string('country');
            $table->string('city');
            $table->string('gender');
            $table->string('status')->default('pending');
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
        Schema::dropIfExists('etickets');
    }
};
