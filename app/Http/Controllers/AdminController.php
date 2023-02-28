<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Admin;
use App\Models\Manager;
use App\Mail\VerifyEmail;
use App\Models\Categories;
use App\Models\Permission;
use App\Mail\ResetPassword;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;


class AdminController extends Controller


{


    public function sendResponse($data, $message, $status = 200){
        $response =[
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response, $status);
     }


     public function sendError($errorData, $message, $status =500){
        $response =[];
        $response['message'] = $message;
        if (!empty($errorData)) {
            $response['data'] = $errorData;
     }
     return response()->json($response, $status);
    }

    public function __construct(){
        $this->middleware('auth:api', ['except'=>['adminSignUp', 'adminLogin','adminLogout','adminverifyEmail','adminresendPin','adminforgotPassword', 'adminverifyPin'
        ,'adminresetPassword','adminCreateSystemUser', 'adminUpdatePermission', 'adminUpdateSystem',
        'showCategory','createCategory']]);
    }


    public function adminSignUp(Request $request){
        $validator = Validator::make($request-> all(),[
            'email' => 'required|string|email:rfc,filter,dns|unique:admins',
            'password'=> 'required|string|min:6|confirmed'

        ]);

        if($validator-> fails()){

            return $this->sendError($validator->errors(), 'Validation Error', 422);
        }

        $user_status  = Admin:: where("email", $request->email)->first();

            if(!is_null($user_status)){
                return $this->sendError([], "Whoops! email already registered", 400);
            }

        $user = Admin:: create(array_merge(
                $validator-> validated(),
                 ['password'=>bcrypt($request->password)]



            ));

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

      $token = $user->createToken('myapptoken')->plainTextToken;


          return $this->sendResponse(
              ['success'=>'true',
              'message'=>'Admin registered successfully.
               Please check your email for a 4-digit pin to verify your email.',
              'token'=>$token
          ], 201);
}



    public function adminLogin(Request $request){
        $validator = Validator::make($request->all(), [
            'email'=> 'required|email:rfc,filter,dns',
            'password' => 'required|string|min:6',
        ]);

        if($validator->fails()){
            return $this->sendError($validator->errors(),'Validation Error', 422);

        }

        if(!$token = auth()->attempt($validator->validated())){
            return $this->sendError([], "Invalid login credentials", 400);
        }

         return $this-> createNewToken($token);



    }

    public function adminverifyEmail(Request $request){
        $validator = Validator::make($request->all(),[
            'code'=> 'required',
            'email' => 'required|email',
        ]);

        if($validator->fails()){
            return $this->sendError(['success' => false, 'message' => $validator->errors()], 422);
        }

        $user = Admin::where('email',$request->email);
        $select = DB::table('password_resets')->where([
                                    'email' => $request->email,
                                    'code' => $request->code
                                      ]);


        if($select->get()->isEmpty()){
            return $this->sendError([
                'success'=> false, 'message' => "Invalid token"
            ], 400);
        }

        $difference = Carbon::now()->diffInSeconds($select->first()->created_at);
        if($difference > 3600){
            return $this->sendError([
                'success'=> false, 'message' => "Token Expired"
            ], 400);
        }


        $select = DB::table('password_resets')
        ->where('email', $request->email)
        ->where('code', $request->code)
        ->delete();

        $user->update([
            'email_verified_at'=> Carbon::now()
        ]);

        return $this->sendResponse(
            ['success' => true,
            'message'=>"Email is verified."], 201);


    }


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
            return $this->sendError(['success' => false, 'message' => $validator->errors()], 422);

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

                return $this->sendResponse(
                    [
                        'success' => true,
                        'message' => "Please check your email for a 4 digit pin"
                    ],
                    200
                );
            }
        } else {
            return $this->sendError(
                [
                    'success' => false,
                    'message' => "This email does not exist"
                ],
                400
            );
        }
    }



    public function adminVerifyPin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'code' => ['required'],
        ]);

        if ($validator->fails()) {
            return $this->sendError(['success' => false, 'message' => $validator->errors()], 422);
        }

        $check = DB::table('password_resets')->where([
            ['email', $request->all()['email']],
            ['code', $request->all()['code']],
        ]);

        if ($check->exists()) {
            $difference = Carbon::now()->diffInSeconds($check->first()->created_at);
            if ($difference > 3600) {
                return $this->sendError(['success' => false, 'message' => "Token Expired"], 400);
            }

            $delete = DB::table('password_resets')->where([
                ['email', $request->all()['email']],
                ['code', $request->all()['code']],
            ])->delete();

            return $this->sendResponse(
                [
                    'success' => true,
                    'message' => "You can now reset your password"
                ],
                200
                );
        } else {
            return $this->sendError(
                [
                    'success' => false,
                    'message' => "Invalid token"
                ],
                401
            );
        }
    }


    public function adminResetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->sendError(['success' => false, 'message' => $validator->errors()], 422);
        }

        $user = Admin::where('email',$request->email);
        $user->update([
            'password'=>bcrypt($request->password)
        ]);

        $token = $user->first()->createToken('myapptoken')->plainTextToken;

        return $this->sendResponse(
            [
                'success' => true,
                'message' => "Your password has been reset",
                'token'=>$token
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
            'name' => 'required',
            'email' => 'required|email|',
            'password' =>'required|confirmed',
            'roles' => 'required'
        ]);
      //  $admin= new Manager();




        if($validator-> fails()){

            return $this->sendError($validator->errors(), 'Validation Error', 422);
        }

        $user_status  = Manager::where("email", $request->email)->first();

            if(!is_null($user_status)){
                return $this->sendError([], "Whoops! email already registered", 400);
            }

        $user = Manager::create(array_merge(
                $validator-> validated(),
                 ['password'=>bcrypt($request->password)]

            ));

            if($request->roles){
                $user->assignRole($request->input('roles'));
            }


            return $this->sendResponse(
                [
                    'success' => true,
                    'message' => "New system user successfully created",
                ],
                200
            );
        // }



    }



    public function adminUpdateSystem(Request $request, $id)
    {
        $this->validate($request, [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$id,
            'password' => 'required|confirmed',
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



    public function adminUpdatePermission(Request $request, Permission $permission){
        $request->validate(['name'=>'required|string|unique:'.config('permission.table_names.permissions','permissions')
        .',name,'.$permission->id]);
        $permission->update(['name'=>$request->name,
        'guard_name'=>'api']);

            return $this->sendResponse(
                [
                    'success' => true,
                    'message' => "Permission updated successfully",
                ],
                200
            );

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
            'expires_in' => auth('api')->factory()->getTTL()* 60,
            'user'=>auth()->user()
        ]);
    }

    }
