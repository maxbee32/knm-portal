<?php

namespace App\Http\Controllers;

use App\Models\Price;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PricingController extends Controller
{


    public function sendResponse($data, $message, $status = 200){
        $response =[
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response, $status);
     }

     public function __construct(){
        $this->middleware('auth:api', ['except'=>['createPrice','showPriceList','updatePriceList']]);
     }

     public function createPrice(Request $request){

        $validator = Validator::make($request-> all(),[
            'visitor_category' => ['required','string'],
            'enterance_fee'=>['required','numeric'],

        ]);

        if($validator->stopOnFirstFailure()-> fails()){
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Validation Error'
            ], 400);

        }

        $user = Price::create(array_merge(
            $validator-> validated()

        ));
        return $this ->sendResponse([
            'success' => true,
             'message' =>'New price added successfully.'

           ],200);
    }



     public function showPriceList(){
        $result = DB::table('prices')
        ->get(array(
            'id',
            'visitor_category',
            'enterance_fee'

        ));
        return $this ->sendResponse([
            'success' => true,
             'message' => $result,

           ],200);
     }

     public function updatePriceList(Request $request, $id){
        $validator = Validator::make($request-> all(),[
            'enterance_fee'=>['required','numeric'],

        ]);

        if($validator->stopOnFirstFailure()-> fails()){
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Validation Error'
            ], 400);

        }

        $user = DB::table('prices')
        ->where('id', $id)
        ->update(['enterance_fee' => $request->enterance_fee]);

        
         return $this ->sendResponse([
            'success' => true,
              'message' => 'Price updated successfully.',

           ],200);

     }








}
