<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class ReservationManagerController extends Controller
{
    //
    public function sendResponse($data, $message, $status = 200){
        $response =[
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response, $status);
     }
    //   public function __construct(){
    //         //  $this->middleware('auth:manager-api', ['except'=>['reservationManagerLogin','checkUserTicket', 'updateUserTicketDeclined','updateUserTicketAprroved','ticketManagerLogout']]);
    //     //   $this->middleware(['role:reservation admin']);
    //     // $this->middleware('role:reservation admin', ['except' => ['reservationManagerLogin']]);
    //  }

     //ticket admin login
     public function reservationManagerLogin(Request $request){
        $validator = Validator::make($request->all(), [
            'email'=> ['required','string'],
            'password' => ['required','string',
             Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()],
        ]);

        if($validator->stopOnFirstFailure()->fails()){
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
        // echo(Auth::getDefaultDriver());
       // echo(config('auth.defaults.guard'));
         return $this-> createNewToken($token);
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
