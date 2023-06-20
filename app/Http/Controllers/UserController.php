<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Eticket;
use App\Mail\VerifyEmail;
use App\Mail\ResetPassword;
use App\Mail\ResendPinEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Stevebauman\Location\Facades\Location;
use Haruncpi\LaravelIdGenerator\IdGenerator;



class UserController extends Controller
{


     public function sendResponse($data, $message, $status = 200){
        $response =[
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response, $status);
     }



public function __construct(){
    $this->middleware('auth:api', ['except'=>['userSignUp', 'userLogin','userLogout','verifyEmail','resendPin','forgotPassword', 'verifyPin','resetPassword',
                                'storeReservation', 'updateReservation','showPendingReservation','showReceipt']]);
}

public function userLogin(Request $request){
    $validator = Validator::make($request->all(), [
        'email'=>['required','email:rfc,filter,dns'],
        'password'=> ['required',
                        'string',
                        Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised()],
    ]);


    if($validator->stopOnFirstFailure()-> fails()){
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Validation Error'
        ], 400);


    }

    if(!$token = auth()->attempt($validator->validated())){
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Invalid login credentials'
        ], 400);

    }

    if(auth()->user()->email_verified_at == null){
        return $this->sendResponse([
            'success' => false,
            'message' => 'Please verify your email before you can continue'
        ], 400);


    }

     return $this-> createNewToken($token);



}

public function userSignUp(Request $request){
    $validator = Validator::make($request-> all(),[
        'email' => ['bail','required','string','email:rfc,filter,dns','unique:users'],
         'password'=> ['required',
                        'string',
                        Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),'confirmed'],
            'username'=>'nullable',


    ]);

     if($validator->stopOnFirstFailure()-> fails()){
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Validation Error'
        ], 400);

    }

    // $ip=request()->ip();
    // $currentUserInfo= Location::get($ip);
    // echo($ip);

        $user = User::create(array_merge(
                $validator-> validated(),
                // ['country'=>$currentUserInfo->countryName],
                // ['city'=>$currentUserInfo->cityName],
                // ['zipCode'=>$currentUserInfo->zipCode],
                // ['region'=>$currentUserInfo->regionName],
                ['password'=>bcrypt($request->password)],

            ));

        if(!$token=auth()->attempt($validator->validated())){
            return $this->sendResponse([
                'success' => false,
                'message' => 'Invalid login credentials'
            ], 400);
        }

        if ($user ){
            $verify2 = DB::table('password_resets')->where([
                ['email',$request->all()['email']]
            ]);

          if($verify2->exists()){
            $verify2->delete();
        }

        $pin =rand(1000, 9999);
        DB::table('password_resets')->insert(
            [
                'email'=>$request->all()['email'],
                'code'=>$pin
            ]
    );

}

      Mail::to($request->email)->send(new VerifyEmail($pin));

      return $this-> createNewToken1($token);
}



public function verifyEmail(Request $request){
  $email = auth()->user()->email;
    $validator = Validator::make($request->all(),[
        'code'=> '',
        'email' => $email,
    ]);

    if($validator->stopOnFirstFailure()-> fails()){
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Validation Error'
        ], 400);

    }


    $user = User::where('email',$email);
    $select = DB::table('password_resets')->where([
                                'email' => $email,
                                'code' => $request->code
                                  ]);


    if($select->get()->isEmpty()){
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Invalid token'
        ], 400);


    }

    $difference = Carbon::now()->diffInSeconds($select->first()->created_at);
    if($difference > 3600){
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Token Expired'
        ], 400);

    }


    $select = DB::table('password_resets')
    ->where('email', $email)
    ->where('code', $request->code)
    ->delete();

    $user->update([
        'email_verified_at'=> Carbon::now()
    ]);
    return $this->sendResponse([
        'success' => true,
        'message' => 'Email is verified.'
    ], 200);




}


public function resendPin(Request $request){
    $validator = Validator::make($request->all(), [
        'email'=> ['required','email:rfc,filter,dns']
    ]);

    if($validator->stopOnFirstFailure()-> fails()){
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Validation Error'
        ], 400);
    }

    $veri = User::where('email', $request->all()['email'])->first();
    if (!$userToken=Auth::fromUser($veri)) {
        return $this->sendResponse([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 400);
     }


    $verify= DB::table('password_resets')->where([
        ['email', $request->all()['email']]
    ]);

    if($verify->exists()){
        $verify->delete();
    }


    $token= random_int(1000, 9999);
    $password_reset = DB::table('password_resets')->insert([
        'email' =>$request->all()['email'],
        'code'=> $token,
        'created_at'=> Carbon::now()
    ]);

    if($password_reset){
        Mail::to($request->all()['email'])->send(new ResendPinEmail($token));

        return $this-> createNewToken3($userToken, $veri);



    }

}



public function forgotPassword(Request $request){
    $validator = Validator::make($request->all(), [
        'email' => ['required', 'string', 'email', 'max:255','exists:users'],
    ]);


    if ($validator->stopOnFirstFailure()-> fails()) {
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Validation Error'
        ], 400);

    }

     $veri = User::where('email', $request->all()['email'])->first();
    if(!$userToken = Auth::fromUser($veri)){
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Invalid credentials'
        ], 400);

    }

    // if (!$userToken=JWTAuth::fromUser($veri)) {
    //     return $this->sendResponse([
    //         'success' => false,
    //         'message' => 'Invalid credentials'
    //     ], 400);
    //  }
     else


    $verify = User::where('email', $request->all()['email'])->exists();




    if ($verify) {
        $verify2 =  DB::table('password_resets')->where([
            ['email', $request->all()['email']]
        ]);

        if ($verify2->exists()) {
            $verify2->delete();
        }

        $token = random_int(1000, 9999);
        $password_reset = DB::table('password_resets')->insert([
            'email' => $request->all()['email'],
            'code' =>  $token,
            'created_at' => Carbon::now()
        ]);




        if ($password_reset) {
            Mail::to($request->all()['email'])->send(new ResetPassword($token));




             return $this-> createNewToken2($userToken, $veri);
        }

    } else {
        return $this->sendResponse([
            'success' => false,
            'message' => 'This email does not exist.'
        ], 400);


    }
}



public function verifyPin(Request $request){
    $email = auth()->user()->email;
    $validator = Validator::make($request->all(), [
        'email' => $email,  //['required', 'string', 'email', 'max:255'],
        'code' => ['required'],
    ]);



    if ($validator->stopOnFirstFailure()-> fails()) {
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Validation Error'
        ], 400);
    }

    $check = DB::table('password_resets')->where([
        ['email', $email],
        ['code', $request->all()['code']],
    ]);

    if ($check->exists()) {
        $difference = Carbon::now()->diffInSeconds($check->first()->created_at);
        if ($difference > 3600) {
            return $this->sendResponse([
                'success' => false,
                'message' => 'Token Expired'
            ], 400);
        }

        $delete = DB::table('password_resets')->where([
            ['email', $email],
            ['code', $request->all()['code']],
        ])->delete();

        return $this->sendResponse([
            'success' => true,
            'message' => 'You can now reset your password'
        ], 200);



    } else {
        return $this->sendResponse([
            'success' => true,
            'message' => 'Invalid token'
        ], 400);

    }
}


public function resetPassword(Request $request){
    $email = auth()->user()->email;
    $validator = Validator::make($request->all(), [
        'email' => $email, //['required','string','email'],
        'password'=> ['required',
                        'string',
                        Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),'confirmed'],
    ]);


    if ($validator->stopOnFirstFailure()-> fails()) {
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Validation Error'
        ], 400);


    }



    $user = User::where('email',$email);
    $user->update([
        'password'=>bcrypt($request->password)
    ]);
    return $this->sendResponse([
        'success' => true,
        'message' => 'Your password has been reset successfully'
    ], 200);



}


   //store booking
   public function storeReservation(Request $request){
    $validator= Validator::make($request-> all(),[

        'fullname'=> 'required|string|',
        'gender'=> 'required|in:Male,Female',
        'country'=> 'required|string',
        'digital_address'=> 'nullable|string',
        'city'=> 'required|string',
        'phone_number'=> 'required|regex:/^(\+\d{1,3}[- ]?)?\d{10}$/|min:10',
        'reservation_date'=> 'required|date',
        // 'numberOfTicket'=>'numeric',
        'children_visitor_category'=>'nullable|in:Ghanaian Children,Non-Ghanaian Children',
        'numberOfChildren'=>'nullable|numeric',
        'adult_visitor_category'=>'nullable|in:Ghanaian Adults,Non-Ghanaian Adults',
        'numberOfAdult'=>'nullable|numeric',
        'status'=>'Pending',
        //'ticketId' => 'required|Unique'


    ]);

    if($validator-> fails()){

        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Validation Error'
        ], 400);

    }

    if(Carbon::now()->format('Y-m-d')> $request->reservation_date){
        return $this->sendResponse([
            'success' => false,
            'message' => 'Date in the past is not allowed. Kindly select a current date'
        ], 400);

    }


    // if($request->numberOfTicket !=$request->numberOfChildren + $request->numberOfAdult ){
    //     return $this->sendResponse([
    //         'success' => false,
    //         'message' => 'Number of tickets should be equal to guest provided.'
    //     ], 400);

    // }

    if($request->numberOfChildren != 0 and $request->children_visitor_category == null){
        return $this->sendResponse([
            'success' => false,
            'message' => 'Please select the visitors category for children.'
        ], 400);
    }

    if($request->numberOfAdult != 0 and $request->adult_visitor_category == null){
        return $this->sendResponse([
            'success' => false,
            'message' => 'Please select the visitors category for adults.'
        ], 400);
    }

    if($request->country=='Ghana' and $request->numberOfAdult != 0 and $request->adult_visitor_category != 'Ghanaian Adults'){
        return $this->sendResponse([
            'success' => false,
            'message' => "Please select the right visitor's category"
        ], 400);
    }

    if($request->country=='Ghana' and $request->numberOfChildren != 0 and $request->children_visitor_category != 'Ghanaian Children'){
        return $this->sendResponse([
            'success' => false,
            'message' => "Please select the right visitor's category"
        ], 400);
    }


    $Id =IdGenerator::generate(['table'=>'etickets','field'=>'ticketid','length'=>10,'prefix'=>'TIC-']);

     Eticket::create(array_merge(
        ['user_id' => optional(Auth()->user())->id],
        ['numberOfTicket'=>$request->numberOfChildren + $request->numberOfAdult],
        ['ticketid'=>$Id],
        $validator-> validated()
    ));

    //echo(Carbon::now()->format('Y-m-d'));
    return $this->sendResponse([
        'success' => true,
        'message' => 'Proceed to make payment.'
    ], 200);



}





    public function updateReservation(Request $request, $id){
        $validator= Validator::make($request-> only(['fullname','gender','phone_number','reservation_date']),[

            'fullname'=> 'required|string|',
            'gender'=> 'required|in:Male,Female',
            //'country'=> 'required|string',
            //'city'=> 'required|string',
            'phone_number'=> 'required|regex:/^(\+\d{1,3}[- ]?)?\d{10}$/|min:10',
            'reservation_date'=> 'required|date' ,
           // 'numberOfTicket'=>'required|numeric',
            //'numberOfChildren'=>'nullable|numeric',
            //'numberOfAdult'=>'nullable|numeric',
           // 'status'=>'Pending',


        ]);

        if($validator-> fails()){
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Validation Error'
            ], 400);

        }


        if($request->numberOfTicket !=$request->numberOfChildren + $request->numberOfAdult ){
            return $this->sendResponse([
                'success' => false,
                'message' => 'Number of tickets should be equal to guest provided'
            ], 400);

        }

        $startDate =carbon::parse($request->reservation_date);

        $data = DB::table('etickets')->select('reservation_date')
        ->where('id',$id)->first();


      //check to see if reschedule date is not more than 21days
        $date = $startDate->diffInDays($data->reservation_date);

            if($date > 21){
            return $this->sendResponse([
                            'success' => false,
                            'message' => 'Sorry you can only reschedule within 21days.',
                            // 'last'=>$startDate->addDays(21),
                            //  'first'=>$data->reservation_date
                        ], 400);
           }

           //check to see if reschedule date is not less than 21days
           $newdate=carbon::parse($data->reservation_date)->diffInDays($startDate);

          if($newdate > 21){
            return $this->sendResponse([
                'success' => false,
                'message' => 'Sorry you can only reschedule within 21days.',
                // 'last'=>$startDate->addDays(21),
                //  'first'=>$data->reservation_date
            ], 400);
          }

          //reschedule date cant not be after current date
           if(Carbon::now()> $startDate){
                return $this->sendResponse([
                    'success' => false,
                    'message' => 'Date in the past is not allowed. Kindly select a date within 21 days'
                ], 400);
            }

            else{

       $reservation= Eticket::findorfail($id);
       $reservation->fullname = $request->fullname;
       $reservation->gender = $request->gender;
       $reservation->phone_number = $request->phone_number;
       $reservation->reservation_date = $request->reservation_date;
       $reservation->save();

      return $this->sendResponse([
        'success' => true,
        'message' => 'You have successfully rescheduled.'
    ], 200);

       }
    }



   public function userLogout(){
    try{

        auth()->logout();
        return response()->json(['success'=>true,'message'=>'Logged out successfully']);
    }catch(\Exception $e){
        return response()->json(['success'=>false, 'message'=> $e->getMessage()]);

    }



}


public function showPendingReservation(){
    $email = auth()->user()->email;
    $user =User::join('etickets','users.id' ,'=','etickets.user_id')
    ->where('etickets.status','pending')
    ->where('users.email',$email)
    ->select(array('ticketid','fullname','phone_number','email','numberOfTicket',
    DB::raw('DATE(reservation_date) AS reservation_date'),
     'numberOfChildren','numberOfAdult','country',))
    ->get();

    return $this ->sendResponse([
        'success' => true,
         'message' => $user,

       ],200);


}


 public function showReceipt(){
    $email = auth()->user()->email;
    $result =User::join('etickets','users.id' ,'=','etickets.user_id')
     ->join('prices', function($join){
        $join->on('etickets.children_visitor_category','=','prices.visitor_category');
        $join->oron('etickets.adult_visitor_category','=','prices.visitor_category');
     })
    ->where('etickets.status','pending')
    ->where('users.email',$email)

    ->select(array('ticketid',
    'fullname',
    'phone_number',
    'email',
    'numberOfTicket',
    'numberOfChildren',
    DB::raw("SUM(CASE
    WHEN children_visitor_category = 'Ghanaian Children' and visitor_category='Ghanaian Children' THEN (etickets.etickets.numberOfChildren * enterance_fee)
    WHEN children_visitor_category = 'Non-Ghanaian Children' and visitor_category='Non-Ghanaian Children' THEN (etickets.etickets.numberOfAdult * enterance_fee) ELSE 0 END)
    AS enterance_fee_for_children"),
    'numberOfAdult',
    DB::raw("SUM(CASE
    WHEN adult_visitor_category = 'Ghanaian Adults' and visitor_category='Ghanaian Adults' THEN (etickets.numberOfAdult * enterance_fee)
    WHEN adult_visitor_category = 'Non-Ghanaian Adults' and visitor_category='Non-Ghanaian Adults' THEN (etickets.numberOfAdult * enterance_fee) ELSE 0 END)
    AS enterance_fee_for_adult"),
    DB::raw("SUM(CASE
    WHEN children_visitor_category = 'Ghanaian Children' and visitor_category='Ghanaian Children' THEN (etickets.numberOfChildren * enterance_fee)
    WHEN children_visitor_category = 'Non-Ghanaian Children' and visitor_category='Non-Ghanaian Children' THEN (etickets.numberOfAdult * enterance_fee)
    WHEN adult_visitor_category = 'Ghanaian Adults' and visitor_category='Ghanaian Adults' THEN (etickets.numberOfAdult * enterance_fee)
    WHEN adult_visitor_category = 'Non-Ghanaian Adults' and visitor_category='Non-Ghanaian Adults' THEN (etickets.numberOfAdult * enterance_fee) ELSE 0 END)
    AS total_amount"),

    DB::raw('DATE(reservation_date) AS reservation_date'),
    ))
   ->groupby('ticketid','fullname',
   'phone_number',
   'email',
   'numberOfTicket',
   'numberOfChildren',
   'numberOfAdult',
   'reservation_date')
    ->get();


    // $result;

    return $this ->sendResponse([
        'success' => true,
         'message' => $result

       ],200);
 }


public function createNewToken($token){


    return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => config('jwt.ttl') * 60,
         'user'=>auth()->user(),
        'message' => "Logged in successfully"
    ]);
}



public function createNewToken1($token){
    return response()->json([
        'access_token' => $token,
        'token_type' => 'bearer',
        'expires_in' => config('jwt.ttl') * 60,
         'user'=>auth()->user(),
        'message'=>'User registered successfully.
        Please check your email for a 4-digit pin to verify your email.'
    ]);
}




public function createNewToken2($userToken, $veri){


    return response()->json([
        'access_token' => $userToken,
        'token_type' => 'bearer',
        'expires_in' => config('jwt.ttl') * 60,
        'user'=>$veri,
        'message' => "Please check your email for a 4 digit pin."
    ]);
}


public function createNewToken3($userToken, $veri){


    return response()->json([
        'access_token' => $userToken,
        'token_type' => 'bearer',
        'expires_in' => config('jwt.ttl') * 60,
         'user'=>$veri,
        'message' => "A verification mail has been resent."
    ]);
}
}
