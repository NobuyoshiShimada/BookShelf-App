<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Override;

class StoreBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $book = $this->route('book');
        $bookId = is_object($book) ? $book->id : null;

        return [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:255',
            'isbn' => 'required|string|size:13|regex:/^[0-9]+$/|unique:books,isbn,'.$bookId,
            'published_date' => 'required|date|before_or_equal:today',
            'description' => 'nullable',
            'image_url' => 'nullable|string|url|max:255',
            'genres' => 'required|array|min:1',
            'genres.*' => 'required|integer|exists:genres,id',
        ];
    }

    #[Override]
    public function messages(): array
    {
        return [
            'title.required' => '書籍のタイトルは必須項目です。',
            'title.string' => 'タイトルは文字列で入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',

            'author.required' => '著者名は必須項目です。',
            'author.string' => '著者名は文字列で入力してください。',
            'author.max' => '著者名は255文字以内で入力してください。',

            'isbn.required' => 'ISBNコードは必須項目です。',
            'isbn.size' => 'ISBNコードはハイフンなしの13文字で入力してください。',
            'isbn.regex' => 'ISBNコードはハイフンなしの「数字のみ」で入力してください。',
            'isbn.unique' => 'このISBNコードの書籍は、すでに登録されています。',

            'published_date.required' => '出版日は必須項目です。',
            'published_date.date' => '正しい日付の形式で入力してください。',
            'published_date.before_or_equal' => '出版日には、今日以前の過去の日付を入力してください。',

            'image_url.url' => '画像URLには「http://」または「https://」から始まる正しいURLを入力してください。',
            'image_url.max' => '画像URLは255文字以内で入力してください。',

            'genres.required' => 'ジャンルは最低でも1つ以上選択してください。',
            'genres.min' => 'ジャンルは最低でも1つ以上選択してください。',
            'genres.*' => '選択されたジャンルの中に、システムに存在しない不正なジャンルが含まれています。',
        ];
    }
}
