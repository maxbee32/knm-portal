<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    use HasFactory;


    protected $casts = [
        'enterance_fee' => 'double'
    ];

protected $fillable =[
    'visitor_category',
    'enterance_fee'
];

    public function user()
    {
        return $this->belongsTo('App\Admin');
    }
}
