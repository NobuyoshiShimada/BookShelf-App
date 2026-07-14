<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BookSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            return;
        }

        $bookData = [
            [
                'number' => 1,
                'title' => '吾輩は猫である',
                'author' => '夏目漱石',
                'isbn' => '9784101010014',
                'published_date' => '1905-01-01',
                'genres' => ['小説'],
                'description' => '明治の文豪・夏目漱石のデビュー作。人間の滑稽な生態を、一匹の猫の視点からユーモラスに鋭く描き出した不朽の名作文学です。'
            ],
            [
                'number' => 2,
                'title' => '人を動かす',
                'author' => 'D・カーネギー',
                'isbn' => '9784422100524',
                'published_date' => '1936-10-01',
                'genres' => ['ビジネス', '自己啓発'],
                'description' => '人間関係の原則を説いた歴史的ベストセラー。他人の心理を理解し、協力を得るための具体的な行動指針が詰まった対人関係のバイブルです。'
            ],
            [
                'number' => 3,
                'title' => 'リーダブルコード',
                'author' => 'Dustin Boswell',
                'isbn' => '9784873115658',
                'published_date' => '2012-06-23',
                'genres' => ['技術書'],
                'description' => '「美しく、読みやすいコード」を書くための実践的なテクニックをまとめた、すべてのエンジニア・プログラマー必読のロングセラー解説書です。'
            ],
            [
                'number' => 4,
                'title' => '7つの習慣',
                'author' => 'スティーブン・R・コヴィー',
                'isbn' => '9784863940246',
                'published_date' => '2013-08-30',
                'genres' => ['ビジネス', '自己啓発'],
                'description' => '全世界でベストセラーを記録し続ける成功哲学の決定版。人格を磨き、人生を豊かにするためのタイムレスな「7つの原則」を提唱しています。'
            ],
            [
                'number' => 5,
                'title' => '坊っちゃん',
                'author' => '夏目漱石',
                'isbn' => '9784101010021',
                'published_date' => '1906-04-01',
                'genres' => ['小説'],
                'description' => '江戸っ子気質で正義感の強い新任教師「坊っちゃん」が、赴任先の四国・松山の学校で巻き起こす大騒動を描いた爽快な日本文学の代表作。'
            ],
            [
                'number' => 6,
                'title' => 'サピエンス全史',
                'author' => 'ユヴァル・ノア・ハラリ',
                'isbn' => '9784309226712',
                'published_date' => '2016-09-08',
                'genres' => ['歴史', '科学'],
                'description' => '無名の存在だったホモ・サピエンスが、なぜ地球の支配者になれたのか。「認知革命」「農業革命」などの視点から人類の歩みを解き明かす世界的名著。'
            ],
            [
                'number' => 7,
                'title' => 'Clean Code',
                'author' => 'Robert C. Martin',
                'isbn' => '9784048930598',
                'published_date' => '2017-12-18',
                'genres' => ['技術書'],
                'description' => 'アジャイルソフトウェア開発の巨匠が教える、職人としてのコードの書き方。優れたコードを書き、保守性の高い設計を維持するための原則が満載です。'
            ],
            [
                'number' => 8,
                'title' => '嫌われる勇気',
                'author' => '岸見一郎・古賀史健',
                'isbn' => '9784478025819',
                'published_date' => '2013-12-13',
                'genres' => ['自己啓発'],
                'description' => 'アルフレッド・アドラーの「アドラー心理学」を、哲学者と青年の対話形式で分かりやすく紐解いた、対人関係の悩みを解消し自由を生きるための指南書。'
            ],
            [
                'number' => 9,
                'title' => '火花',
                'author' => '又吉直樹',
                'isbn' => '9784163902302',
                'published_date' => '2015-03-11',
                'genres' => ['小説'],
                'description' => 'お笑いコンビ・ピースの又吉直樹による第153回芥川賞受賞作。売れない芸人たちの葛藤と友情、お笑いという熱狂の世界の光と影を瑞々しく描く。'
            ],
            [
                'number' => 10,
                'title' => 'FACTFULNESS',
                'author' => 'ハンス・ロスリング',
                'isbn' => '9784822289607',
                'published_date' => '2019-01-11',
                'genres' => ['ビジネス', '科学'],
                'description' => 'データや客観的な事実に基づき、世界を正しく見る方法を教えてくれる本。私たちが陥りがちな10の思い込みを暴き、希望ある現実を浮き彫りにします。'
            ],
            [
                'number' => 11,
                'title' => 'コンテナ物語',
                'author' => 'マルク・レビンソン',
                'isbn' => '9784822251468',
                'published_date' => '2007-01-18',
                'genres' => ['ビジネス', '歴史'],
                'description' => '「ただの四角い箱」であるコンテナが、世界の物流コストを劇的に下げ、グローバル経済をどのように一変させたのかを描き出した、興奮に満ちた経済ノンフィクション。'
            ],
        ];

        foreach ($bookData as $data) {
            $book = Book::firstOrCreate(
                ['isbn' => $data['isbn']],
                [
                    'user_id' => $user->id,
                    'title' => $data['title'],
                    'author' => $data['author'],
                    'published_date' => $data['published_date'],
                    'description' => $data['description'],
                    'image_url' => "https://placehold.co/200x300/e2e8f0/475569?text={$data['number']}",
                ]
            );

            $genreIds = Genre::whereIn('name', $data['genres'])->pluck('id')->toArray();

            $book->genres()->sync($genreIds);
        }
    }
}
