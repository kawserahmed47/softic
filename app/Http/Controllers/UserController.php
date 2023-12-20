<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = User::query();

         // Filter by name if the 'name' query parameter is present
         if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->input('name') . '%');
        }


        $users = $query->get();
        if(count($users)){
            $data['status'] = true;
            $data['message'] = "All users loaded successfully!";
            $data['users'] = $users;
            return response()->json($data,  200);
        } else {
            $data['status'] = false;
            $data['message'] = "Sorry, no users found!";
            $data['users'] = $users;
            return response()->json($data,  404);
        }
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'date_of_birth' => 'required|date',
        ]);

        if ($validator->fails()) {
            $data['status'] = false;
            $data['message'] = "Validation failed!";
            $data['errors'] =  $validator->errors();
            return response()->json($data, 422);
        }

        try {
            $user = new User();
            $user->name = $request->name;
            $user->email = $request->email;
            $user->date_of_birth = $request->date_of_birth;
            $user->password = Hash::make(date('Ymd', strtotime($request->date_of_birth)));
            $user->save();

            $data['user'] = $user;
            $data['status'] = true;
            $data['message'] = "Successfully saved the user!";
            return response()->json($data, 201);
        } catch (\Throwable $th) {
            $data['status'] = false;
            $data['message'] = "Sorry, failed to save the user!";
            $data['errors'] = $th;
            return response()->json($data, 500);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $user = User::find($id);
        if($user) {
            $data['status'] = true;
            $data['message'] = "User loaded to show";
            $data['user'] = $user;
            return response()->json($data, 200);
        } else {
            $data['status'] = false;
            $data['message'] = "Sorry, nothing find to show";
            $data['user'] = $user;
            return response()->json($data, 404);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $user = User::find($id);

        if($user) {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string',
                'email' => [
                    'required',
                    'email',
                    Rule::unique('users')->ignore($id),
                ],
                'date_of_birth' => 'required|date',
            ]);

            if ($validator->fails()) {
                $data['status'] = false;
                $data['message'] = "Validation failed!";
                $data['errors'] =  $validator->errors();
                return response()->json($data, 422);
            }

            try {
                $user->name = $request->name;
                $user->email = $request->email;
                $user->date_of_birth = $request->date_of_birth;
                $user->save();

                $data['user'] = $user;
                $data['status'] = true;
                $data['message'] = "Successfully updated the record!";
                return response()->json($data, 200);
            } catch (\Throwable $th) {
                $data['status'] = false;
                $data['message'] = "Sorry, failed to save the record!";
                $data['errors'] = $th;
                return response()->json($data, 500);
            }
        } else {
            $data['status'] = false;
            $data['message'] = "Sorry, nothing find to update";
            $data['user'] = $user;
            return response()->json($data, 404);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::find($id);
        if($user){
            try {
                $user->delete();
                $data['user'] = $user;
                $data['status'] = true;
                $data['message'] = "Successfully deleted the record!";
                return response()->json($data, 200);
            } catch (\Throwable $th) {
                $data['status'] = false;
                $data['message'] = "Sorry, failed to delete the record!";
                $data['errors'] = $th;
                return response()->json($data, 500);
            }
        } else {
            $data['status'] = false;
            $data['message'] = "Sorry, nothing find to delete!";
            $data['user'] = $user;
            return response()->json($data, 404);
        }
    }
}
