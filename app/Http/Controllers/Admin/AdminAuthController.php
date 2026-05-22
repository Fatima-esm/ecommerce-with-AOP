<?php

namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;



class AdminAuthController extends Controller
{
    public function login(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator= Validator::make($request->all(),[
            'email'=>['required','email'],
            'password'=>['required','string']
        ]);
        if($validator->fails()){
            return response()->json(['errors'=>$validator->errors()],401);
        }


        //authenticate the admin
        if(!Auth::guard('admin')->attempt($request->only(['email','password']) )){
            return response()->json(['errors'=>"invalid email or password"],401);
        }

        //create token for admin
        $admin= Admin::where('email',$request->email)->first();


            $token= $admin->createToken('myAppToken')->plainTextToken;

        return response()->json([
            'status'=>200,
            'admin'=>$admin,
            'token'=>$token,
            'message'=>"the admin logging successfully",
        ],200);

    }

    public function logout(Request $request): \Illuminate\Http\JsonResponse
    {
        request()->user()->currentAccessToken()->delete();
        Auth::guard('admin')->logout();
        return response()->json(['message' => 'Logged out successfully'], 200);}

}
