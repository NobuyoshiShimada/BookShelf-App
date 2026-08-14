<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Override;

class StoreReadingPlanRequest extends FormRequest
{
    /**
     * リクエストを実行するユーザーの認証・実行権限チェック
     *
     * @return bool 常に実行を許可する場合は true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 新規読書計画登録時における各入力項目のバリデーションルール定義
     *
     * セキュリティ・重複防御仕様:
     * パラメータ改ざんを防ぐため user_id はリクエストに含めず、
     * データベースの一意制約と連動して、現在ログイン中のユーザー (auth()->id()) が
     * 同一の book_id で二重に計画を登録しようとしたケースを Rule::unique で弾きます。
     *
     * @return array<string, ValidationRule|array<mixed>|string> バリデーションルールの配列
     */
    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'integer',
                'exists:books,id',
                Rule::unique('reading_plans')->where(function ($query) {
                    return $query->where('user_id', auth()->id());
                }),
            ],
            'target_date' => 'required|date|after_or_equal:today',
        ];
    }

    /**
     * バリデーションエラー発生時に返却するカスタム日本語メッセージの定義
     *
     * @return array<string, string> 属性名とエラー規則に対応するエラーメッセージの配列
     */
    #[Override]
    public function messages()
    {
        return [
            'book_id.required' => '書籍を選択してください。',
            'book_id.integer' => '書籍IDは整数で入力してください。',
            'book_id.exists' => '選択された書籍は存在しません。',
            'book_id.unique' => 'この書籍は既に進行中の読書計画が存在します。',
            'target_date.required' => '期日は必須です。',
            'target_date.date' => '期日は有効な日付形式で入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を指定してください。',
        ];
    }
}
