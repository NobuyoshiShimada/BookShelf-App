<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class ReviewRequest extends FormRequest
{
    /**
     * リクエストを実行するユーザーのログイン認証権限チェック
     *
     * @return bool 認証済みで実行を許可する場合は true、未認証は false
     */
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * レビューの投稿・更新時における各入力項目のバリデーションルール定義
     *
     * バリデーション仕様:
     * 評価点数（rating）は1〜5の整数、コメント本文（comment）は必須で最大1000文字までに制限します。
     * 改ざんを防ぐため、user_id や book_id のルールはここには含めません。
     *
     * @return array<string, ValidationRule|array<mixed>|string> バリデーションルールの配列
     */
    public function rules(): array
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:1000',
        ];
    }

    /**
     * バリデーションエラー発生時に画面へ返却するカスタム日本語メッセージの定義
     *
     * @return array<string, string> 属性名とエラー規則に対応するエラーメッセージの配列
     */
    #[Override]
    public function messages(): array
    {
        return [
            'rating.required' => '評価は必須です。',
            'rating.integer' => '評価は整数で入力してください。',
            'rating.min' => '評価は1〜5の整数で入力してください。',
            'rating.max' => '評価は1〜5の整数で入力してください。',
            'comment.required' => 'コメントは必須です。',
            'comment.string' => 'コメントは文字列で入力してください。',
            'comment.max' => 'コメントは1000文字以内で入力してください。',
        ];
    }
}
