<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * 🤖 API認証管理コントローラー
 */
class AuthController extends Controller
{
    /**
     * APIログイン（Bearer Tokenを発行）
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        // ユーザーの存在確認 ＆ パスワードの一致チェック
        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['ログイン情報が正しくありません。'],
            ]);
        }

        // 既存の同一デバイス名トークンを消去（重複防止）してから新規発行
        $user->tokens()->where('name', $request->device_name)->delete();
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    /**
     * APIログアウト（現在使用中のトークンを破棄）
     */
    public function logout(Request $request): JsonResponse
    {
        // 現在リクエストに紐付いているトークンをDBから物理削除して失効させる
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'ログアウトしました（トークンを失効しました）。',
        ]);
    }
}
