<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ManagerController extends Controller
{
    //
    public function sendResponse($data, $message, $status = 200){
        $response =[
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response, $status);
     }
     public function __construct(){
        $this->middleware('auth:api', ['except'=>['ticketManagerLogin','checkUserTicket', 'updateUserTicketDeclined','updateUserTicketAprroved','ticketManagerLogout']]);
    }

     //ticket admin login
     public function ticketManagerLogin(Request $request){
        $validator = Validator::make($request->all(), [
            'email'=> ['required','string'],
            'password' => ['required','string',
             Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        if($validator->stopOnFirstFailure()-> fails()){
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Validation Error'
            ], 400);
        }

        if(!$token = auth()->guard('manager-api')->attempt($validator->validated())){
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Invalid login credentials'
            ], 400);

        }

         return $this-> createNewToken($token);
    }


    //ticket admin check users ticket and give them access to the venue
    public function checkUserTicket(){
        $date = \Carbon\Carbon::today()->subDays(5);
        $date1 = Carbon::today();
        $user =DB::table('tickets')
        -> whereBetween(DB::raw('DATE(reservation_date)'),[$date, $date1 ])
         ->select(array('ticketId','fullname',
         DB::raw("DATE(reservation_date) As reservation_date"),
         'numberOfTicket',
         'children_visitor_category',
         'numberOfChildren',
         'adult_visitor_category',
         'numberOfAdult',
         'status'
         ))
        ->get();

        return $this ->sendResponse([
            'success' => true,
             'message' => $user,

           ],200);

    }

    public function updateUserTicketAprroved(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => ['required','string','in:Approved',]

        ]);

        if($validator->stopOnFirstFailure()->fails()){
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Validation Error'
            ], 400);
}

         DB::table('tickets')
        ->where('id', $id)
        ->update(['status' => $request->status
    ]);

    return $this ->sendResponse([
        'success' => true,
          'message' => 'Ticket approved successfully .',

       ],200);
    }


    public function updateUserTicketDeclined(Request $request, $id){
        $validator = Validator::make($request->all(), [
            'status' => ['required','string','in:Declined',]

        ]);

        if($validator->stopOnFirstFailure()->fails()){
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Validation Error'
            ], 400);
}

         DB::table('tickets')
        ->where('id', $id)
        ->update(['status' => $request->status
    ]);

    return $this ->sendResponse([
        'success' => true,
          'message' => 'Ticket declined successfully .',

       ],200);
    }



    public function ticketManagerLogout(){
        try{

            auth()-> logout();
            return response()->json(['success'=>true,'message'=>'Logged out successfully']);
        }catch(\Exception $e){
            return response()->json(['success'=>false, 'message'=> $e->getMessage()]);

        }

    }


    public function createNewToken($token){
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user'=>auth()->guard('manager-api')->user(),
            'message'=>'Logged in successfully.'
        ]);
    }
}
