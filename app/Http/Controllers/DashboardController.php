<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function sendResponse($data, $message, $status = 200){
        $response =[
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response, $status);
     }

    public function __construct(){
        $this->middleware('auth:api', ['except'=>['getAllUser','getNumberOfTickets7','getTicketAmount7',
        'getTransaction7']]);
    }


    public function getAllUser(){
        $user =DB::table('users')
         ->select(array(
            DB::raw("COUNT(email) As users"),
         ))
        ->get();

        return $this ->sendResponse([
            'success' => true,
             'message' => $user,

           ],200);

    }


    public function getNumberOfTickets7(){
        $date = \Carbon\Carbon::today()->subDays(7);
        $date1 = Carbon::today();
        $user = DB::table('etickets')
        -> whereBetween(DB::raw('DATE(reservation_date)'),[$date, $date1 ])
        ->select(array(
            DB::raw("COUNT(ticketid) As tickets"),
        ))
        ->get();

        return $this ->sendResponse([
            'success' => true,
             'message' => $user,

           ],200);
    }


    public function getTicketAmount7(){
        $date = \Carbon\Carbon::today()->subDays(7);
        $date1 = Carbon::today();
        $user = User::join('etickets','users.id' ,'=','etickets.user_id')
        ->join('prices', function($join){
           $join->on('etickets.children_visitor_category','=','prices.visitor_category');
           $join->oron('etickets.adult_visitor_category','=','prices.visitor_category');
        })
       -> whereBetween(DB::raw('DATE(reservation_date)'),[$date, $date1 ])
       ->select(array(
        DB::raw("SUM(CASE
        WHEN children_visitor_category = 'Ghanaian Children' and visitor_category='Ghanaian Children' THEN (number_of_children * enterance_fee)
        WHEN children_visitor_category = 'Non-Ghanaian Children' and visitor_category='Non-Ghanaian Children' THEN (number_of_children * enterance_fee)
        WHEN adult_visitor_category = 'Ghanaian Adults' and visitor_category='Ghanaian Adults' THEN (number_of_adult * enterance_fee)
        WHEN adult_visitor_category = 'Non-Ghanaian Adults' and visitor_category='Non-Ghanaian Adults' THEN (number_of_adult * enterance_fee) ELSE 0 END)
        AS total_amount"),
       ))

       ->get();

       return $this ->sendResponse([
        'success' => true,
         'message' => $user,

       ],200);

    }


    public function getTransaction7(){
        $date = \Carbon\Carbon::today()->subDays(7);
        $date1 = Carbon::today();
        $user = User::join('etickets','users.id' ,'=','etickets.user_id')
        ->join('prices', function($join){
            $join->on('etickets.children_visitor_category','=','prices.visitor_category');
            $join->oron('etickets.adult_visitor_category','=','prices.visitor_category');
        })
        -> whereBetween(DB::raw('DATE(reservation_date)'),[$date, $date1 ])
        ->select(array(
                       'fullname',
                       DB::raw("SUM(CASE
                       WHEN children_visitor_category = 'Ghanaian Children' and visitor_category='Ghanaian Children' THEN (number_of_children)
                       WHEN children_visitor_category = 'Non-Ghanaian Children' and visitor_category='Non-Ghanaian Children' THEN (number_of_children )
                       WHEN adult_visitor_category = 'Ghanaian Adults' and visitor_category='Ghanaian Adults' THEN (number_of_adult )
                       WHEN adult_visitor_category = 'Non-Ghanaian Adults' and visitor_category='Non-Ghanaian Adults' THEN (number_of_adult) END)
                       AS Number_Of_Ticket"),
                       'ticketid',
                       DB::raw("DATE(reservation_date) As reservation_date"),
                       DB::raw("SUM(CASE
       WHEN children_visitor_category = 'Ghanaian Children' and visitor_category='Ghanaian Children' THEN (number_of_children * enterance_fee)
       WHEN children_visitor_category = 'Non-Ghanaian Children' and visitor_category='Non-Ghanaian Children' THEN (number_of_children * enterance_fee)
       WHEN adult_visitor_category = 'Ghanaian Adults' and visitor_category='Ghanaian Adults' THEN (number_of_adult * enterance_fee)
       WHEN adult_visitor_category = 'Non-Ghanaian Adults' and visitor_category='Non-Ghanaian Adults' THEN (number_of_adult * enterance_fee) ELSE 0 END)
       AS total_amount"),
       'status'

        ))
        ->groupby('fullname', 'ticketid','reservation_date','status')
        ->get();


        return $this ->sendResponse([
            'success' => true,
             'message' => $user,

           ],200);
    }

}
