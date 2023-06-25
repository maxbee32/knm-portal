<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Validator;

class PermissionController extends Controller
{
    public function sendResponse($data, $message, $status = 200){
        $response =[
            'data' => $data,
            'message' => $message
        ];
        return response()->json($response, $status);
     }

     public function __construct(){
        $this->middleware('auth:api', ['except'=>['createPermission','updatePermission']]);
       // $this->middleware('permission:role-create',['only'=>['createRole']]);
    }
    //
    public function createPermission(Request $request)
    {
        $validator = Validator::make($request-> all(),[
            'name' => ['bail','required','unique:permissions'],
        ]);

        if($validator->stopOnFirstFailure()-> fails()){
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Validation Error'
            ], 400);

        }

        Permission::create(['name' => $request->input('name')]);
        // , 'guard_name'=>'admin-api'

        return $this->sendResponse([
            'success' => true,
            'message' => 'Permission created successfully'
        ], 200);


    }



    public function updatePermission(Request $request,$id){
        $validator = Validator::make($request->all(), [
            'name' => ['required','unique:permissions','string'],

        ]);

        if($validator->stopOnFirstFailure()->fails()){
            return $this->sendResponse([
                'success' => false,
                'data'=> $validator->errors(),
                'message' => 'Validation Error'
            ], 400);
}

         DB::table('permissions')
        ->where('id', $id)
        ->update(['name' => $request->name
    ]);

    return $this ->sendResponse([
        'success' => true,
          'message' => 'Permission updated successfully.',

       ],200);
    }
}
