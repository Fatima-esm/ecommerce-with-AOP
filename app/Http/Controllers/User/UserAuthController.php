<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class UserAuthController extends Controller
{

    //to register for User
    public function register(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email:filter', 'max:255'],
            'name' => ['required', 'max:255'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()]);
        }

        if ($user = User::create($validator->validated())) {
            return response()->json([
                'status' => 200,
                'data' => $user,
                'message' => "registered successfully",
            ]);
        } else {
            return response()->json([
                'message' => "an error occurred"
            ], 500);
        }
    }

    //logging for user
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator= Validator::make($request->all(),[
            'email'=>['required','email'],
            'password'=>['required','string'],
        ]);

        //return first validation error if validation fails
        if($validator->fails()){
            return response()->json([
                'errors'=>$validator->errors()
            ],401);
        }
        if(!Auth::attempt($request->only('email','password'))){
            return response()->json([
               'errors'=>"invalid email or password ",
            ],401);
        }
        $user= User::where('email',$request->email)->first();

        $token= $user->createToken("myAppToken")->plainTextToken;

        return response()->json([
            'status'=>200,
            'data'=>$user,
            'token'=>$token,
            'message'=>"the user logging successfully",
        ],200);



    }


    // user Logout
    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        request()->user()->currentAccessToken()->delete();
        Auth::guard('web')->logout();
        return response()->json(['message' => 'Logged out successfully'], 200);
    }

}
