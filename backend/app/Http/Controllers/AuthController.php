<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\QueryException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8',
            ], [
                'name.required' => '名前は必須です。',
                'name.max' => '名前は255文字以内で入力してください。',
                'email.required' => 'メールアドレスは必須です。',
                'email.email' => '有効なメールアドレスを入力してください。',
                'email.max' => 'メールアドレスは255文字以内で入力してください。',
                'email.unique' => 'このメールアドレスは既に登録されています。',
                'password.required' => 'パスワードは必須です。',
                'password.min' => 'パスワードは8文字以上で入力してください。',
            ]);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth-token')->plainTextToken;

            return response()->json([
                'token' => $token,
                'user' => $user,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => '入力内容に誤りがあります。',
                'errors' => $e->errors(),
            ], 422);
        } catch (QueryException $e) {
            if (str_contains($e->getMessage(), 'Duplicate entry')) {
                return response()->json([
                    'message' => 'このメールアドレスは既に登録されています。',
                ], 422);
            }
            return response()->json([
                'message' => '登録処理中にエラーが発生しました。',
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'message' => '予期せぬエラーが発生しました。',
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => '有効なメールアドレスを入力してください。',
            'password.required' => 'パスワードは必須です。',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            throw ValidationException::withMessages([
                'email' => ['認証情報が正しくありません。'],
            ]);
        }

        $user = User::where('email', $request->email)->first();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'ログアウトしました。']);
    }

    public function user(Request $request)
    {
        return response()->json($request->user());
    }
}
