<!-- <?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Haruncpi\LaravelIdGenerator\IdGenerator;

class TicketController extends Controller
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
        $this->middleware('auth:api', ['except'=>['storeReservation']]);
    }

              //store booking
    public function storeReservation(Request $request){
        $validator= Validator::make($request-> all(),[

            'fullname'=> 'required|string',
            'gender'=> 'required|in:Male,Female',
            'country'=> 'required|string',
            'region'=> 'required|string',
            'city'=> 'required|string',
            'phone_number'=> 'required|regex:/^(\+\d{1,3}[- ]?)?\d{10}$/|min:10',
            'reservation_date'=> 'required|date',
            'numberOfTicket'=>'required|numeric',
            'numberOfChildren'=>'nullable|numeric',
            'numberOfAdult'=>'nullable|numeric',
            'status'=>'Pending',
            'ticketId' => 'required'


        ]);

        if($validator-> fails()){

            return $this->sendError($validator->errors(), 'Validation Error', 422);
        }

        if(Carbon::now()> $request->reservation_date){
            return $this->sendError([
                'success'=> false, 'message' => "Date in the past is not allowed. Kindly select a current date"
            ], 400);
        }


        $Id =IdGenerator::generate(['table'=>'tickets','length'=>10,'prefix'=>'TIC-']);

         Ticket::create(array_merge(
            ['user_id' => optional(Auth()->user())->id],
            ['ticketId'=>$Id],
            $validator-> validated()
        ));

        return $this->sendResponse(
            ['success'=>'true',
            'message'=>'Reservation completed successfully.'



        ], 201);
    }

} -->
