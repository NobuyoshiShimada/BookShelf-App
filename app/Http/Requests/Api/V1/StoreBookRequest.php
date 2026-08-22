<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreBookRequest extends FormRequest
{
    /**
     * リクエストの実行権限チェック
     *
     * Sanctum（auth:sanctum）ミドルウェアが手前で認証を
     * 強制担保するため、リクエストクラス内では一律で実行を許可します。
     *
     * @return bool 常に実行を許可する場合は true
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * 新規書籍登録時における各入力項目のバリデーションルール定義
     *
     *
     * リクエスト改ざんによる特権昇格を防ぐため、user_id ルールはここに一切含めません。
     * また、ISBNは13桁の文字列サイズチェックと、booksテーブル内での一意性を厳格にチェックします。
     *
     * @return array<string, ValidationRule|array<mixed>|string> バリデーションルールの配列
     */
    public function rules(): array
    {

        return [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'nullable|string|size:13|unique:books,isbn,'.($this->route('book')?->id ?? 'NULL'),
            'published_date' => 'nullable|date',
            'description' => 'nullable|string',
            'image_url' => 'nullable|url|max:255',
            'genres' => 'required|array|min:1',
            'genres.*' => 'exists:genres,id',
        ];
    }

    /**
     * バリデーションエラー発生時にクライアントへ返却するカスタム日本語メッセージの定義
     *
     * @return array<string, string> 属性名とエラー規則に対応するエラーメッセージの配列
     */
    #[Override]
    public function messages(): array
    {
        return [
            'title.required' => 'タイトルは必須です。',
            'title.string' => 'タイトルは文字列で入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',

            'author.required' => '著者名は必須です。',
            'author.string' => '著者名は文字列で入力してください。',
            'author.max' => '著者名は255文字以内で入力してください。',

            'isbn.size' => 'ISBNは13桁で入力してください。',
            'isbn.string' => 'ISBNは文字列で入力してください。',
            'isbn.unique' => 'そのISBNは既に使用されています。',

            'published_date.date' => '出版日は有効な日付形式で入力してください。',

            'description.string' => '説明は文字列で入力してください。',

            'image_url.url' => '画像URLは有効なURL形式で入力してください。',
            'image_url.max' => '画像URLは255文字以内で入力してください。',

            'genres.required' => 'ジャンルは1つ以上選択してください。',
            'genres.array' => 'ジャンルは配列で入力してください。',
            'genres.min' => 'ジャンルは1つ以上選択してください。',
            'genres.*' => '選択されたジャンルは存在しません。',
        ];
    }
}
