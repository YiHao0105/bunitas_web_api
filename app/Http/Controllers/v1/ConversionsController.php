<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Conversions;
use Validator;
use DB;

class ConversionsController extends Controller
{
    public function save(Request $request){
        $validator = Validator::make($request->all(), [
            'room_id'=>'required',
            'sender_id' => 'required',
            'message_type' => 'required',
            'message' => 'required',
            'status'=>'required'
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }

        $data = Conversions::create($request->all());
        if (is_null($data)) {
            $response = [
            'data'=>$data,
            'message' => 'error',
            'status' => 500,
        ];
        return response()->json($response, 200);
        }
        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function getById(Request $request){
        $validator = Validator::make($request->all(), [
            'room_id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }

        $data = Conversions::where('room_id',$request->room_id)->get();

        if (is_null($data)) {
            $response = [
                'success' => false,
                'message' => 'Data not found.',
                'status' => 404
            ];
            return response()->json($response, 404);
        }

        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }

    public function search(Request $request) {
        $validator = Validator::make($request->all(), [
            'id' => 'required',
            'search' => 'required',
        ]);

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }

        if ($validator->fails()) {
            $response = [
                'success' => false,
                'message' => 'Validation Error.', $validator->errors(),
                'status'=> 500
            ];
            return response()->json($response, 404);
        }

        $id = $request->id;

        $data = DB::table('chat_rooms')
        ->select('a.first_name as sender_first_name','a.id as sender_id','b.first_name as receiver_name','b.id as receiver_id','a.last_name as sender_last_name','a.cover as sender_cover','b.last_name as receiver_last_name','b.cover as receiver_cover','a.type as sender_type','b.type as receiver_type','c.message as last_message','chat_rooms.last_message_type as last_message_type','chat_rooms.updated_at as updated_at', 'd.message')
        ->join('users as a', 'chat_rooms.sender_id', '=', 'a.id')
        ->join('users as b', 'chat_rooms.receiver_id', '=', 'b.id')
        ->join('view_last_message as c', 'chat_rooms.id', '=', 'c.room_id')
        ->join('conversions as d', 'chat_rooms.id', '=', 'd.room_id')
        ->where(function ($query) use ($id) {
            $query->where('chat_rooms.sender_id', $id)
                ->orWhere('chat_rooms.receiver_id', $id);
            })
        ->where('d.message', 'like', '%'.$request->search.'%')
        ->get();

        $response = [
            'data'=>$data,
            'success' => true,
            'status' => 200,
        ];
        return response()->json($response, 200);
    }
}
