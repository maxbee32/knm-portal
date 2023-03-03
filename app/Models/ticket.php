<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    use HasFactory;

    protected $guarded=[];

    // protected $fiilable = [
    //     'user_id',
    //     'fullname',
    //     'country',
    //     'city',
    //     'phone_number',
    //     'reservation_date',
    //     'numberOfChildren',
    //     'numberOfAdult',
    //     'numberOfTicket',
    //     'ticketId',
    //     'gender',
    //     'status'

    // ];


    public function user()
    {
        return $this->belongsTo('App\User');
    }
}
