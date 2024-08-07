<?php

namespace App\Http\Controllers\v1\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use SadiqSalau\LaravelOtp\Facades\Otp;

class OtpController extends Controller
{
    public function verify(Request $request){

        try {


            $request->validate([
                'email'    => ['required', 'string', 'email', 'max:255'],
                'code'     => ['required', 'string']
            ]);

            $otp = Otp::identifier($request->email)->attempt($request->code);

            if($otp['status'] != Otp::OTP_PROCESSED)
            {

                return errorResponse('Something Went Wrong. Please Try Again Later', 403, ['otp' => 'The verifiable OTP is empty', 'status' => $otp['status']]);
            }

            return successResponse($otp['result'], "OTP Verified Successfully, Loggin In Now.", 200);
        } catch (Exception $e){
            return errorResponse('Something Went Wrong. Please Try Again Later', 500, $e->errors());
        }
    }

    public function resend(Request $request){
        try {
            $request->validate([
                'email'    => ['required', 'string', 'email', 'max:255']
            ]);

            $otp = Otp::identifier($request->email)->update();

            if($otp['status'] != Otp::OTP_SENT)
            {
                return errorResponse('Something Went Wrong. Please Try Again Later', 403, ['otp' => 'Error While Resending OTP', 'status' => $otp['status']]);
            }
            /* return __(); */
            return successResponse($otp['status'], "OTP Resent Successfully", 200);


        } catch (Exception $e){
            return errorResponse('Something Went Wrong. Please Try Again Later', 500, $e->errors());
        }
    }
}
