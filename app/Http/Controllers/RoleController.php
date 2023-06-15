<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class RoleController extends Controller
{

    public function sendResponse($data, $message, $status = 200){
        $response =[
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response, $status);
     }

    //  protected function guard(){
    //     return Auth::guard('admin-api');
    // }

     public function __construct(){
        $this->middleware('auth:api', ['except'=>['createRole','updateRole']]);
       // $this->middleware('permission:role-create',['only'=>['createRole']]);
    }
    //
    public function createRole(Request $request)
    {
        $validator = Validator::make($request-> all(),[
            'name' => ['bail','required','unique:roles'],
            'permission'=>['required']
        ]);

        if($validator->stopOnFirstFailure()-> fails()){
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Validation Error'
            ], 400);

        }

        $role =Role::create(['name' => $request->input('name'), 'guard_name'=>'admin-api']);
        $role->givePermissionTo($request->input('permission'));


        return $this->sendResponse([
            'success' => true,
            'message' => 'Role created successfully'
        ], 200);



    }

    public function updateRole(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'name' => ['required','unique:roles','string'],

        ]);

        if($validator->stopOnFirstFailure()->fails()){
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Validation Error'
            ], 400);
}

         DB::table('roles')
        ->where('id', $id)
        ->update(['name' => $request->name
    ]);

    return $this ->sendResponse([
        'success' => true,
          'message' => 'Role updated successfully.',

       ],200);
    }

    public function deleteRole($id)
    {
        $user=Role::find($id);
        if (is_null($user)){
            return $this ->sendResponse([
                'success' => true,
                 'message' => 'Role not found.'

               ],200);
           }

           else {
             DB::beginTransaction();
             try{
                $user->delete();
                DB::commit();
                return $this ->sendResponse([
                    'success' => true,
                     'message' => 'Role has been permanently removed from the system.'

                   ],200);
             } catch(Exception $err){
                DB::rollBack();
             }


        }
}
}
