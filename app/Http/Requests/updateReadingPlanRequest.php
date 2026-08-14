<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class updateReadingPlanRequest extends FormRequest
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
     * 読書計画更新時における入力項目のバリデーションルール定義
     *
     * 境界条件仕様:
     * 変更後の目標期日（target_date）は必須かつ有効な日付形式であり、
     * なおかつ本日以降の日付（after_or_equal:today）であることを厳密にチェックします。
     *
     * @return array<string, ValidationRule|array<mixed>|string> バリデーションルールの配列
     */
    public function rules(): array
    {
        return [
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
            'target_date.required' => '期日は必須です。',
            'target_date.date' => '期日は有効な日付形式で入力してください。',
            'target_date.after_or_equal' => '期日は今日以降の日付を指定してください。',
        ];
    }
}
