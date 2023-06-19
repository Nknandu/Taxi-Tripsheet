<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class VendorAuthController extends Controller
{
    public function showVendorLoginForm()
    {
        return view('pages.user.auth.login', ['url' => route('user.login-view'), 'title'=>'Vendor']);
    }

    public function vendorLogin(Request $request)
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
                    'message' => __('messages.validation_error'),
                    'message_code' => 'validation_error',
                    'errors' => $validator->errors()->all()
                ]);
            }

            if (isset($request->remember) && $request->remember == true) {
                $remember = true;
            } else {
                $remember = false;
            }

            $user = User::where('email', $request->email)->where('user_type', 'Vendor')->first();
            if(!$user)
            {
                return response()->json([
                    'success' => false,
                    'status_code' => 200,
                    'message' => __('messages.no_vendor_exists'),
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
                    'message' => __('messages.invalid_credentials'),
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
                    'message' => __('messages.something_went_wrong'),
                    'message_code' => 'try_catch',
                    'exception' => $exception->getMessage(),
                    'errors' => []
                ], 500);
        }


    }

    public function vendorLogout(Request $request) {
        auth('web')->logout();
        return redirect('/');
    }
}
