<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Override;

class GenreRequest extends FormRequest
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
     * ジャンルの登録・更新時における各入力項目のバリデーションルール定義
     *
     * 一意性仕様:
     * ジャンル名は必須かつ最大255文字。更新時は、現在編集中の対象ジャンル自身のID
     * （$this->route('genre')?->id）をunique制約の評価対象から除外します。
     *
     * @return array<string, ValidationRule|array<mixed>|string> バリデーションルールの配列
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255|unique:genres,name,'.($this->route('genre')?->id ?? 'NULL'),
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
            'name.required' => 'ジャンル名は必須です。',
            'name.string' => 'ジャンル名は文字列で入力してください。',
            'name.max' => 'ジャンル名は255文字以内で入力してください。',
            'name.unique' => 'そのジャンル名は既に使用されています。',
        ];
    }
}
