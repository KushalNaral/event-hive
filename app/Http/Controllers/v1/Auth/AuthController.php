<?php

namespace App\Http\Controllers\v1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\RegisterRequest;

use App\Models\User;

use App\Notifications\OtpNotification;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

use App\Otp\UserRegistrationOtp;
use SadiqSalau\LaravelOtp\Facades\Otp;

use Illuminate\Auth\Events\Login;

use Exception;

class AuthController extends Controller
{

    public function register(RegisterRequest $request){

        try {

            DB::beginTransaction();

            $otp = Otp::identifier($request->email)->send(
                new UserRegistrationOtp(
                    name: $request->name,
                    email: $request->email,
                    password: $request->password,
                    phone_number: $request->phone_number
                ),
                Notification::route('mail', $request->email)
            );

            DB::commit();
            return successResponse($otp, "User Registered Successfully. An OTP Has Been Sent To Your Email.", 200);

        } catch(Exception $e) {
            DB::rollback();
            return errorResponse($e->getMessage(), $e->getStatusCode(), $e->errors() );
        }
    }


    public function login(Request $request){

        $validate = Validator::make($request->all(), [
            'email' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validate->fails()) {
            return errorResponse('Login Failed. Please Check Your Credentials', 401, $validate->errors());
        }

        //checking if user exists
        $user = User::where( 'email', $request->email )->first();

        // Check password
        if (!$user || !Hash::check($request->password, $user->password)) {
            return errorResponse('Invalid Credentials, Please Verify', 401, []);
        }

        if($user->first_login == 1){
            $user->update(['first_login' => 0]);
        }

        $data['token'] = $user->createToken($user->email)->accessToken;
        $data['user'] = $user;

        return successResponse($data, "User Logged In Successfully", 200);
    }

}
