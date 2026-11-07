<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'name.required' => 'وارد کردن نام الزامی است.',
            'email.required' => 'وارد کردن ایمیل الزامی است.',
            'email.email' => 'فرمت ایمیل معتبر نیست.',
            'email.unique' => 'این ایمیل قبلاً ثبت شده است.',
            'password.required' => 'وارد کردن رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = new User;
        $user->name = $req->name;
        $user->role = 'Customre';
        $user->email = $req->email;
        $user->password = Hash::make($req->password);
        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'ثبت‌نام با موفقیت انجام شد.',
            'user' => $user,
            'token' => $token
        ], 201);
    }
    public function login(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'email' => 'required|string|email',
            'password' => 'required|min:8',
        ], [
            'email.required' => 'وارد کردن ایمیل الزامی است.',
            'password.required' => 'وارد کردن رمز عبور الزامی است.',
            'email.email' => 'فرمت ایمیل معتبر نیست.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $req->email)->first();

        if (!$user || !Hash::check($req->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'ایمیل یا رمز عبور اشتباه است.'
            ], 401);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'message' => 'ورود با موفقیت انجام شد.',
            'user' => $user,
            'token' => $token
        ], 201);
    }
    public function forgotPassword(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'email' => 'email|required'
        ], [
            'email.required' => 'وارد کردن ایمیل الزامی است.',
            'email.email' => 'فرمت ایمیل معتبر نیست.',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $req->email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'کاربری با این ایمیل پیدا نشد.'
            ], 404);
        }

        $user->password = Hash::make($user->email);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'رمز عبور با موفقیت تغییر کرد.'
        ], 200);
    }

    public function logout(Request $req)
    {
        $req->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'با موفقیت از حساب کاربری خارج شدید.'
        ]);
    }
    public function user(Request $req)
    {
        return response()->json([
            'success' => true,
            'user' => $req->user()
        ]);
    }
}
