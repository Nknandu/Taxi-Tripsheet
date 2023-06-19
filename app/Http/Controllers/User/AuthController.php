<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function showUserLoginForm()
    {
        return view('pages.user.auth.login', ['url' => route('login-view')]);
    }

    public function showUserRegisterForm()
    {
        return view('pages.user.auth.register', ['url' => route('register-view')]);
    }

    public function showUserForgotPasswordForm()
    {
        return view('pages.user.auth.forgot_password', ['url' => route('forgot-password-view')]);
    }

    public function userLogin(Request $request)
    {
        try
        {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|min:6'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' =>'Validation Error',
                    'message_code' => 'validation_error',
                    'errors' => $validator->errors()->all()
                ]);
            }

            if (isset($request->remember) && $request->remember == true) {
                $remember = true;
            } else {
                $remember = false;
            }

            $user = User::where('email', $request->email)->first();
            if(!$user)
            {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => 'User Not Exists',
                    'message_code' => 'login_failed',
                    'errors' => []
                ]);
            }

            if (auth('web')->attempt($request->only(['email', 'password']), $remember)) {
                return response()->json([
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Successfully Logged In',
                    'message_code' => 'login_success',
                    'url' => route('user.dashboard')
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => 'Invalid Credentials',
                    'message_code' => 'login_failed',
                    'errors' => []
                ]);
            }
        }
        catch (\Exception $exception)
        {
            return response()->json(
                [
                    'success' => false,
                    'status_code' => 500,
                    'message' => 'Something Went Wrong',
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }


    }

    public function userLogout(Request $request) {
        auth('web')->logout();
        return redirect('/');
    }
}
