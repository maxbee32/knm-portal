<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Payment;
use App\Mail\PaymentMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{


    public function sendResponse($data, $message, $status = 200){
        $response =[
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response, $status);
     }



public function __construct(){
    $this->middleware('auth:api', ['except'=>['makePayment']]);
}


public function makePayment(Request $request){
    $email = auth()->user()->email;
    $validator = Validator::make($request-> all(),[
        'mode_of_payment' => ['required','in:Visa Card,Master Card,Momo'],
        'cardNumber'=>['string'],
        'phone_number' => ['string'],
        'amount'=>['required'],
        'currency_type'=>['required'],
        'cvc'=>['string'],
        'exp_month'=>['string'],
        'exp_year'=>['string'],
    ]);


    if($validator->stopOnFirstFailure()->fails()){
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Validation Error'
        ], 400);
    }


    $result =User::join('etickets','users.id' ,'=','etickets.user_id')
    ->join('prices', function($join){
       $join->on('etickets.children_visitor_category','=','prices.visitor_category');
       $join->oron('etickets.adult_visitor_category','=','prices.visitor_category');
    })
   ->where('etickets.status','pending')
   ->where('users.email',$email)
   ->select(
    DB::raw("SUM(CASE
    WHEN children_visitor_category = 'Ghanaian Children' and visitor_category ='Ghanaian Children' THEN (number_of_children * enterance_fee)
    WHEN children_visitor_category = 'Non-Ghanaian Children' and visitor_category ='Non-Ghanaian Children' THEN (number_of_children * enterance_fee)
    WHEN adult_visitor_category = 'Ghanaian Adults' and visitor_category ='Ghanaian Adults' THEN (number_of_adult * enterance_fee)
    WHEN adult_visitor_category = 'Non-Ghanaian Adults' and visitor_category ='Non-Ghanaian Adults' THEN (number_of_adult * enterance_fee) ELSE 0 END)
    AS total_amount")
    )
    // ->get();
    // echo($result);
    ->pluck('total_amount');

    if($request->mode_of_payment != 'Momo' and $request->cardnumber == null and $request->cvc== null and $request->exp_month == null and $request->exp_year == null){
        return $this->sendResponse([
            'success' => false,
            'message' => 'Please provide the necessary information to complete the Credit Card transaction.'
        ], 400);
    }

    if($request->mode_of_payment != 'Momo' and $request->exp_month == null and $request->exp_year == null){
        return $this->sendResponse([
            'success' => false,
            'message' => 'Please provide the necessary information to complete the Credit Card transaction.'
        ], 400);
    }

    if($request->mode_of_payment == 'Momo' and $request->phone_number == null){
        return $this->sendResponse([
            'success' => false,
            'message' => 'Please provide the necessary information to complete the Momo transaction.'
        ], 400);
    }

    // $result->implode('') convert array to string
    if($result->implode('')!= $request->amount){
        return $this->sendResponse([
            'success' => false,
            'message' => 'Please the payment amount should be equal to the total amount on the ticket .'
        ], 400);
}


    Payment::create(array_merge(
        ['user_id' => optional(Auth()->user())->id],
        $validator-> validated()
    ));


    Mail::to($email)->send(new PaymentMail);

    return $this->sendResponse([
        'success' => true,
        'message' => 'Payment made successfully.'
    ], 200);


}


}
