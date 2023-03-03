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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticketId');
            $table->integer('numberOfTicket');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('fullname');
            $table->string('phone_number');
            $table->dateTime('reservation_date');
            $table->integer('numberOfChildren')->default(0);
            $table->integer('numberOfAdult')->default(0);
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
        Schema::dropIfExists('tickets');
    }
};
