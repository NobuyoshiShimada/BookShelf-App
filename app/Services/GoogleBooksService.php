<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class GoogleBooksService
{
    /**
     * ISBNコードを基にGoogle Books APIから書籍情報を取得・トランスフォーム
     *
     * @param  string  $isbn  13桁のISBNコード文字列
     * @return array<string, string|null> 加工済みの書籍データ配列
     *
     * @throws Exception API通信失敗、または該当書籍が存在しない場合
     */
    public function fetchByIsbn(string $isbn): array
    {
        $response = Http::withoutVerifying()
            ->timeout(10)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0'])
            ->get('https://www.googleapis.com/books/v1/volumes', [
                'q' => 'isbn:'.$isbn,
                'key' => env('GOOGLE_BOOKS_API_KEY'),
            ]);

        if ($response->failed() || ! isset($response->json()['items'][0]['volumeInfo'])) {
            throw new Exception('該当する書籍情報が見つかりませんでした。');
        }

        $volumeInfo = $response->json()['items'][0]['volumeInfo'];

        $publishedDate = $volumeInfo['publishedDate'] ?? null;
        $publishedDate = match (strlen($publishedDate ?? '')) {
            4 => $publishedDate.'-01-01',
            7 => $publishedDate.'-01',
            default => $publishedDate,
        };

        return [
            'title' => $volumeInfo['title'] ?? '',
            'author' => collect($volumeInfo['authors'] ?? [])->implode(', '),
            'published_date' => $publishedDate,
            'description' => $volumeInfo['description'] ?? '',
            'image_url' => str_replace('http://', 'https://', $volumeInfo['imageLinks']['thumbnail'] ?? ''),
        ];
    }
}
