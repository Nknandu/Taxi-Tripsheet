<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AdminAuthController extends Controller
{
    public function showAdminLoginForm()
    {
        return view('pages.admin.auth.login', ['url' => route('admin.login-view'), 'title'=>'Admin']);
    }

    public function adminLogin(Request $request)
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

            if (Auth::guard('admin')->attempt($request->only(['email', 'password']), $remember)) {
                return response()->json([
                    'success' => true,
                    'status_code' => 200,
                    'message' => 'Successfully Logged In',
                    'message_code' => 'login_success',
                    'url' => route('admin.dashboard')
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

    public function adminLogout(Request $request) {
        auth('admin')->logout();
        return redirect('admin/');
    }
}
