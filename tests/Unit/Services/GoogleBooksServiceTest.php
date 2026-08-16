<?php

namespace Tests\Unit\Services;

use App\Services\GoogleBooksService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use Exception;

/**
 * 🧪 GoogleBooksService ユニット（単体）テスト
 *
 */
class GoogleBooksServiceTest extends TestCase
{
    /**
     * 🧪 正常系: 4桁の出版年およびHTTPサムネイルURLが正しくトランスフォームされるか
     */
    public function test_fetchByIsbn_4桁の出版年とHTTP画像URLを美しく加工して返却する(): void
    {
        // 💡 外部通信が発生しないようにモックデータをセット
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title'         => 'ルポ現代の職人',
                            'authors'       => ['佐藤 一郎'],
                            'publishedDate' => '2026',
                            'description'   => '伝統技術の現場。',
                            'imageLinks'    => ['thumbnail' => 'http://example.com']
                        ]
                    ]
                ]
            ], 200)
        ]);

        $service = new GoogleBooksService();
        $result = $service->fetchByIsbn('9784798157573');

        // 💡 サービスのトランスフォーム（加工結果）を厳格にアサート
        $this->assertEquals('ルポ現代の職人', $result['title']);
        $this->assertEquals('佐藤 一郎', $result['author']);
        $this->assertEquals('2026-01-01', $result['published_date']); //
        $this->assertEquals('伝統技術の現場。', $result['description']);
        $this->assertEquals('https://example.com', $result['image_url']);
    }

    /**
     * 🧪 正常系: 7桁の出版年月が正しくフォーマットされるか
     */
    public function test_fetchByIsbn_7桁の出版年月は日付形式に補完される(): void
    {
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title'         => 'Laravel実践ガイド',
                            'publishedDate' => '2026-08',
                        ]
                    ]
                ]
            ], 200)
        ]);

        $service = new GoogleBooksService();
        $result = $service->fetchByIsbn('9784798157573');

        $this->assertEquals('2026-08-01', $result['published_date']);
    }

    /**
     * 🧪 正常系: 著者が複数存在する場合にカンマ区切りで綺麗に結合されるか
     */
    public function test_fetchByIsbn_複数の著者はカンマスペース区切りで結合される(): void
    {
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title'   => '共著の技術書',
                            'authors' => ['山田 太郎', '鈴木 次郎', '佐藤 三郎'],
                        ]
                    ]
                ]
            ], 200)
        ]);

        $service = new GoogleBooksService();
        $result = $service->fetchByIsbn('9784798157573');

        // 💡 collect()->implode(', ') の挙動が正しく作用しているか検証
        $this->assertEquals('山田 太郎, 鈴木 次郎, 佐藤 三郎', $result['author']);
    }

    /**
     * 🧪 異常系: APIから該当書籍が返されなかった（見つからなかった）場合に例外をスローするか
     */
    public function test_fetchByIsbn_該当書籍が存在しない場合は例外を投げる(): void
    {
        // 💡 検索にヒットしなかった空配列をフェイクとして設定
        Http::fake([
            'https://www.googleapis.com/books/v1/volumes*' => Http::response([
                'items' => []
            ], 200)
        ]);

        $service = new GoogleBooksService();

        // Exceptionクラスが特定のメッセージでスローされることをテストコードに期待させる定義
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('該当する書籍情報が見つかりませんでした。');

        // 例外が投げられるため、これ以降の行は実行されず、例外発生でテストクリアになります
        $service->fetchByIsbn('9999999999999');
    }
}
