<?php

namespace App\Http\Controllers;

use Exception;
use Carbon\Carbon;
use App\Models\Admin;
use App\Models\Manager;
use App\Mail\VerifyEmail;
use App\Models\Categories;
use App\Mail\ResetPassword;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Permission;

class AdminController extends Controller


{

    public function sendResponse($data, $message, $status = 200){
        $response =[
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response, $status);
     }

    public function __construct(){
        $this->middleware('auth:api', ['except'=>['adminSignUp', 'adminLogin','adminLogout','adminverifyEmail','adminresendPin','adminForgotPassword', 'adminPinVerify'
        ,'adminResetPassword','adminCreateSystemUser', 'adminUpdatePermission', 'adminUpdateSystem','deleteSystemUser',
        'showCategory','createCategory','showSystemUsers']]);
    }


    public function adminSignUp(Request $request){

        $validator = Validator::make($request-> all(),[
            'email' => ['bail','required','string','email:rfc,filter,dns','unique:admins'],
            'username'=>['required','string','unique:admins'],
            'password'=> ['required','string',
            Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),'confirmed'],

        ]);

        if($validator->stopOnFirstFailure()-> fails()){
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Validation Error'
            ], 400);

        }



        $user = Admin::create(array_merge(
                $validator-> validated(),
                 ['password'=>bcrypt($request->password)]

            ));

            if(!$token = auth()->guard('admin-api')->attempt($validator->validated())){
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
      return $this-> createNewToken1($token);
    // Mail::to($request->email)->send(new VerifyEmail($pin));

    //   $token = $user->createToken('myapptoken')->plainTextToken;


}



    public function adminLogin(Request $request){
        $validator = Validator::make($request->all(), [
            'username'=> ['required','string'],
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

        if(!$token = auth()->guard('admin-api')->attempt($validator->validated())){
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Invalid login credentials'
            ], 400);

        }

         return $this-> createNewToken($token);
    }


    // public function adminverifyEmail(Request $request){
    //     $validator = Validator::make($request->all(),[
    //         'code'=> 'required',
    //         'email' => 'required|email',
    //     ]);

    //     if($validator->fails()){
    //         return $this->sendError(['success' => false, 'message' => $validator->errors()], 422);
    //     }

    //     $user = Admin::where('email',$request->email);
    //     $select = DB::table('password_resets')->where([
    //                                 'email' => $request->email,
    //                                 'code' => $request->code
    //                                   ]);


    //     if($select->get()->isEmpty()){
    //         return $this->sendError([
    //             'success'=> false, 'message' => "Invalid token"
    //         ], 400);
    //     }

    //     $difference = Carbon::now()->diffInSeconds($select->first()->created_at);
    //     if($difference > 3600){
    //         return $this->sendError([
    //             'success'=> false, 'message' => "Token Expired"
    //         ], 400);
    //     }


    //     $select = DB::table('password_resets')
    //     ->where('email', $request->email)
    //     ->where('code', $request->code)
    //     ->delete();

    //     $user->update([
    //         'email_verified_at'=> Carbon::now()
    //     ]);

    //     return $this->sendResponse(
    //         ['success' => true,
    //         'message'=>"Email is verified."], 201);


    // }


    public function adminResendPin(Request $request){
        $validator = Validator::make($request->all(), [
            'email'=> 'required|email:rfc,filter,dns'
        ]);


        if($validator->fails()){
          return $this->sendError($validator->errors(),'Validation Error', 422);

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
            Mail::to($request->all()['email'])->send(new VerifyEmail($token));

            return $this->sendResponse(
                ['success' => true,
                'message'=>"A verification mail has been resent."], 201);


        }

    }


    public function adminForgotPassword(Request $request){
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
        ]);

        if ($validator->fails()) {
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Validation Error'
            ], 400);

        }


        $veri = Admin::where('email', $request->all()['email'])->first();
       if (!$userToken=Auth::fromUser($veri)) {
        return $this->sendResponse([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 400);
     }


        $verify = Admin::where('email', $request->all()['email'])->exists();

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
                // return $this->sendResponse(
                //     [
                //         'success' => true,
                //         'message' => "Please check your email for a 4 digit pin"
                //     ],
                //     200
                // );
            }
        } else {
            return $this->sendResponse(
                [
                    'success' => false,
                    'message' => "This email does not exist"
                ],
                400
            );
        }
    }





      public function adminPinVerify(Request $request){
        $email = auth()->guard('admin-api')->user()->email;
        // echo($email);
        $validator = Validator::make($request->all(), [
            'email' => $email,  //['required', 'string', 'email', 'max:255'],
            'code' => ['required'],
        ]);

        if ($validator->fails()) {
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


    public function adminResetPassword(Request $request)
    {
        $email = auth()->guard('admin-api')->user()->email;
        $validator = Validator::make($request->all(), [
            'email' => $email,
            'password'=> ['required',
                        'string',
                        Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),'confirmed'],
    ]);

    if ($validator->fails()) {
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Validation Error'
        ], 400);


    }

        $user = Admin::where('email',$email);
        $user->update([
            'password'=>bcrypt($request->password)
        ]);



        return $this->sendResponse(
            [
                'success' => true,
                'message' => "Your password has been reset",

            ],
            200
        );
    }

    public function adminLogout(){
        try{

            auth()-> logout();
            return response()->json(['success'=>true,'message'=>'Logged out successfully']);
        }catch(\Exception $e){
            return response()->json(['success'=>false, 'message'=> $e->getMessage()]);

        }



    }

      // create new users and assign role
    public function adminCreateSystemUser(Request $request){
        $validator = Validator::make($request->all(),[
            'username' => 'required',
            'email' => 'required|email|unique:managers',
            'roles' => 'required',
            'permission'=>'required',
            'password'=> ['required',
                        'string',
                        Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),'confirmed'],

        ]);




      if($validator->stopOnFirstFailure()-> fails()){
        return $this->sendResponse([
            'success' => false,
            'data'=> $validator->errors(),
            'message' => 'Validation Error'
        ], 400);

    }

        $user = Manager::create(array_merge(
                $validator-> validated(),
                ['password'=>bcrypt($request->password)]

            ));

           if($request->roles){
                $user->assignRole($request->input('roles'));
                  $user->givePermissionTo($request->input('permission'));
            }


            return $this->sendResponse(
                [
                    'success' => true,
                    'message' => "New system user successfully created",
                ],
                200
            );

    }



    public function adminUpdateSystem(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:managers'.$id,
            'password'=> ['required',
                        'string',
                        Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),'confirmed'],
            'roles' => 'required'
        ]);


        $input = $request->all();
        if(!empty($input['password'])){
            $input['password'] = bcrypt($input['password']);
        }else{
            $input = Arr::except($input,array('password'));
        }


        $user = Manager::find($id);
        $user->update($input);
        DB::table('model_has_roles')->where('model_id',$id)->delete();


        $user->assignRole($request->input('roles'));


        return $this->sendResponse(
            [
                'success' => true,
                'message' => "System user updated successfully",
            ],
            200
        );
    }


    public function deleteSystemUser($id)
    {
        $user=Manager::find($id);
        if (is_null($user)){
            return $this ->sendResponse([
                'success' => true,
                 'message' => 'Manager not found.'

               ],200);
           }

           else {
             DB::beginTransaction();
             try{
                $user->delete();
                DB::commit();
                return $this ->sendResponse([
                    'success' => true,
                     'message' => 'Account has been permanently removed from the system.'

                   ],200);
             } catch(Exception $err){
                DB::rollBack();
             }


        }


    }

     public function showSystemUsers(){
        $user =DB::table('managers')
        ->join('model_has_roles','managers.id', '=' ,'model_has_roles.model_id')
        ->join('model_has_permissions','managers.id', '=' ,'model_has_permissions.model_id')
        ->join('roles', function($join){
            $join->on('model_has_roles.role_id','=','roles.id');
        })
         ->join('permissions', function($pjoin){
            $pjoin->on('model_has_permissions.permission_id','=','permissions.id');
         })
         ->select(array('username','email','roles.name as roles','permissions.name as permissions'))
        ->get();

        return $this ->sendResponse([
            'success' => true,
             'message' => $user,

           ],200);


    }


    public function createCategory(Request $request){
       // $image = $request->file('image')->store('public/categories');
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'description' => 'required',
            'image' =>'required|image|mimes:jpg,png,jpeg,gif,svg' //| //$image

        ]);
        if($validator-> fails()){

            return $this->sendError($validator->errors(), 'Validation Error', 422);
        }

        $image =$request->file('image')->store('public/images');


         Categories::create(array_merge(
            ['image'=>$image],
            $validator-> validated()

        ));
        return $this->sendResponse(
            [
                'success' => true,
                'message' => "New category created",
            ],
            200
        );
    }

   //get all records for categories
    public function showCategory(){
        $categories = Categories::all();

        return $this->sendResponse(($categories),
            [
                'success' => true,
                'message' => "Categories Retrieved Successfully.",
            ],
            200
        );
    }


    public function updateCategory(Request $request, $id){
        $categories = $request->all();

        $validator = Validator::make($categories,[
            'name' => 'required',
            'description' => 'required',
            'image' =>'required|image|mimes:jpg,png,jpeg,gif,svg' //| //$image

        ]);
        if($validator-> fails()){

            return $this->sendError($validator->errors(), 'Validation Error', 422);
        }

         $Category = Categories::find($id);
         $Category->name =$categories['name'];
         $Category->description =$categories['description'];
         $image = $Category->image;
         if($request->hasFile('image')){
            Storage::delete($Category->image);
         $image =$request->file('image')->store('public/images');
         }

        Categories::create(array_merge(
            ['image'=>$image],
            $validator-> validated()

        ));
        return $this->sendResponse(
            [
                'success' => true,
                'message' => "New category created",
            ],
            200
        );
    }


    public function createNewToken($token){
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user'=>auth()->guard('admin-api')->user(),
            'message'=>'Logged in successfully.'
        ]);
    }


    public function createNewToken1($token){
        return response()->json([
            // 'success'=>'true',
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => config('jwt.ttl') * 60,
            'user'=>auth()->guard('admin-api')->user(),
            'message'=>'Admin registered successfully.'
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
    }
