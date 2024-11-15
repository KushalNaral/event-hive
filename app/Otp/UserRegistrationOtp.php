<?php

namespace App\Otp;

use App\Models\Role;
use SadiqSalau\LaravelOtp\Contracts\OtpInterface as Otp;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserRegistrationOtp implements Otp
{
    /**
     * Constructs Otp class
     */
    public function __construct(
        protected string $name,
        protected string $email,
        protected string $password,
        protected string $phone_number,
    )
    {
        //
    }

    /**
     * Processes the Otp
     *
     * @return mixed
     */
    public function process()
    {

        DB::beginTransaction();

        $user = User::create([
            'name' => $this->name,
            'phone_number' => $this->phone_number,
            /* 'password' => $this->generateRandomPassword(12), */
            'password' => bcrypt($this->password),
            'email_verified_at'     => now(),
            'email' => $this->email,
        ]);

        $token = $user->createToken($this->email)->accessToken;
        $user->token = $token;

        $nonAdminRole = Role::where('name', 'non-admin')->first();

        $user->roles()->attach($nonAdminRole->id);
        event(new Registered($user));

        Auth::login($user);

        DB::commit();

        return $user;
    }

    private function generateRandomPassword($length){
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
        $charactersLength = strlen($characters);
        $randomPassword = '';
        for ($i = 0; $i < $length; $i++) {
            $randomPassword .= $characters[rand(0, $charactersLength - 1)];
        }
        return bcrypt($randomPassword);
        /* return bcrypt('password'); */
    }

}
