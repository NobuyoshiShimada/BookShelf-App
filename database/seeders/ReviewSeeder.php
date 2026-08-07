<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books = Book::all();

        $users = User::all();

        $templates = [
            1 => '少し物足りなさを感じました。',
            2 => '理解するのに時間がかかりそうです。',
            3 => '内容は普通でした。',
            4 => 'とても分かりやすく一気に読めました。',
            5 => 'とても素晴らしい本でした。',
        ];

        foreach ($books as $book) {

            $reviewCount = rand(2, 4);

            $shuffledUsers = $users->shuffle();

            for ($i = 0; $i < $reviewCount; $i++) {
                if (! $shuffledUsers->has($i)) {
                    break;
                }

                $reviewer = $shuffledUsers->get($i);

                $rating = rand(1, 5);

                Review::create([
                    'user_id' => $reviewer->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'comment' => $templates[$rating],
                ]);
            }

        }

        // $reviewsData = [
        //     // 1. 吾輩は猫である (book_id: 1) ➔ 3件
        //     ['book_id' => 1, 'user_id' => 1, 'rating' => 4, 'comment' => '猫の視点から描かれる人間模様がユーモラスで、今読んでも新鮮な発見があります。'],
        //     ['book_id' => 1, 'user_id' => 3, 'rating' => 5, 'comment' => '独特の文体が心地よく、明治の空気感を感じられる大好きな名作文学です。'],
        //     ['book_id' => 1, 'user_id' => 4, 'rating' => 3, 'comment' => '少し言葉遣いが難しい部分もありますが、猫のキャラクターが魅力的で楽しめました。'],

        //     // 2. 人を動かす (book_id: 2) ➔ 3件
        //     ['book_id' => 2, 'user_id' => 1, 'rating' => 5, 'comment' => '人間関係に悩んだら必ず読み返す一冊。すべての原則がビジネスと日常生活のどちらにも活きています。'],
        //     ['book_id' => 2, 'user_id' => 3, 'rating' => 4, 'comment' => '相手の立場に立つことの大切さを痛感させられます。定期的に読み直して実践したい。'],
        //     ['book_id' => 2, 'user_id' => 5, 'rating' => 5, 'comment' => 'コミュニケーションの本質がここに詰まっています。後輩全員におすすめしたい名著です。'],

        //     // 3. リーダブルコード (book_id: 3) ➔ 4件
        //     ['book_id' => 3, 'user_id' => 2, 'rating' => 5, 'comment' => 'チーム開発をする上で必須の知識。読みやすいコードを書く具体的なテクニックが満載です。'],
        //     ['book_id' => 3, 'user_id' => 3, 'rating' => 5, 'comment' => '新卒エンジニアの時に出会いたかった本。シンプルで明快な解説が素晴らしく、何度も読み返しています。'],
        //     ['book_id' => 3, 'user_id' => 4, 'rating' => 4, 'comment' => 'コードの命名規則やコメントの書き方など、明日からすぐに現場で実践できる内容ばかりでした。'],
        //     ['book_id' => 3, 'user_id' => 5, 'rating' => 4, 'comment' => 'リファクタリングの重要性がよく分かります。エンジニアなら手元に置いておくべきバイブル。'],

        //     // 4. 7つの習慣 (book_id: 4) ➔ 3件
        //     ['book_id' => 4, 'user_id' => 1, 'rating' => 5, 'comment' => '人生のコンパスとなるような一冊。小手先のテクニックではなく、人格を磨くことの大切さを学びました。'],
        //     ['book_id' => 4, 'user_id' => 2, 'rating' => 4, 'comment' => 'ボリュームがありますが、それだけの価値があります。主体的に生きることの意味を深く考えさせられました。'],
        //     ['book_id' => 4, 'user_id' => 4, 'rating' => 4, 'comment' => '時間管理のマトリクスは特に参考になりました。自分の人生の優先順位を見直すきっかけになります。'],

        //     // 5. 坊っちゃん (book_id: 5) ➔ 2件
        //     ['book_id' => 5, 'user_id' => 1, 'rating' => 4, 'comment' => '坊っちゃんの真っ直ぐで不器用なキャラクターが小気味よく、最後まで一気に読めました！'],
        //     ['book_id' => 5, 'user_id' => 5, 'rating' => 3, 'comment' => 'ストーリー展開が早くコミカルで面白いです。夏目漱石の作品の中でも特に親しみやすい。'],

        //     // 6. サピエンス全史 (book_id: 6) ➔ 3件
        //     ['book_id' => 6, 'user_id' => 2, 'rating' => 5, 'comment' => '人類の歴史を全く新しい視点から切り取った衝撃的な一冊。認知革命の解説には目から鱗が落ちました。'],
        //     ['book_id' => 6, 'user_id' => 3, 'rating' => 5, 'comment' => '虚構を信じる能力が人類を発展させたという考察が非常に深く、知的好奇心が刺激されっぱなしでした。'],
        //     ['book_id' => 6, 'user_id' => 4, 'rating' => 4, 'comment' => '歴史だけでなく、現代の私たちの幸福や社会システムについても考えさせられる素晴らしい名著です。'],

        //     // 7. Clean Code (book_id: 7) ➔ 3件
        //     ['book_id' => 7, 'user_id' => 1, 'rating' => 4, 'comment' => 'プロとしてのコードの書き方を厳しく教えてくれる本。実践するのは大変ですが、意識が高まります。'],
        //     ['book_id' => 7, 'user_id' => 3, 'rating' => 5, 'comment' => 'リーダブルコードの一歩先を行く内容。美しい設計とテストコードの大切さが論理的に学べます。'],
        //     ['book_id' => 7, 'user_id' => 5, 'rating' => 4, 'comment' => '保守性の高いシステムを作るための原則が詰まっています。プログラマーとして成長したい人におすすめ。'],

        //     // 8. 嫌われる勇気 (book_id: 8) ➔ 3件
        //     ['book_id' => 8, 'user_id' => 2, 'rating' => 5, 'comment' => 'アドラー心理学を対話形式で学べる名著。「課題の分離」を知ることで、人間関係が本当に楽になりました。'],
        //     ['book_id' => 8, 'user_id' => 4, 'rating' => 3, 'comment' => '納得できる部分と、少し極端に感じる部分がありましたが、生き方を見直す強いフックになる本です。'],
        //     ['book_id' => 8, 'user_id' => 5, 'rating' => 4, 'comment' => '自分の幸せは自分で決めるという力強いメッセージに救われました。何度も読み直したいです。'],

        //     // 9. 火花 (book_id: 9) ➔ 2件
        //     ['book_id' => 9, 'user_id' => 1, 'rating' => 4, 'comment' => '芸人の世界の熱量と、残酷なまでの現実が美しい文章で描かれていて、胸が熱くなりました。'],
        //     ['book_id' => 9, 'user_id' => 3, 'rating' => 3, 'comment' => '純文学らしい独特のテンポ感があります。ラストシーンの情景描写が非常に印象的でした。'],

        //     // 10. FACTFULNESS (book_id: 10) ➔ 3件
        //     ['book_id' => 10, 'user_id' => 2, 'rating' => 5, 'comment' => 'いかに自分がドラマチックすぎる世界の見方をしていたかを思い知らされる、全人類必読の書。'],
        //     ['book_id' => 10, 'user_id' => 4, 'rating' => 5, 'comment' => '客観的なデータに基づいて世界を正しく見る重要性が分かります。ニュースを見る目がガラリと変わりました。'],
        //     ['book_id' => 10, 'user_id' => 5, 'rating' => 4, 'comment' => 'グラフや図解が豊富で分かりやすく、世界の真の姿を前向きに捉えられる素晴らしい内容でした。'],

        //     // 11. コンテナ物語 (book_id: 11) ➔ 3件
        //     ['book_id' => 11, 'user_id' => 1, 'rating' => 5, 'comment' => 'ただの箱であるコンテナが世界経済の構造を変えた歴史。物流のイノベーションが描かれた傑作ノンフィクション。'],
        //     ['book_id' => 11, 'user_id' => 3, 'rating' => 4, 'comment' => '地味なテーマに見えますが、標準化を巡るビジネスの戦いや規格争いのドラマがあり、非常に面白いです。'],
        //     ['book_id' => 11, 'user_id' => 4, 'rating' => 4, 'comment' => '現代のグローバル化がどのようにして成し遂げられたのか、その裏舞台を知ることができる貴重な一冊。'],
        // ];

        // foreach ($reviewsData as $data) {
        //     Review::create([
        //         'user_id' => $data['user_id'],
        //         'book_id' => $data['book_id'],
        //         'rating' => $data['rating'],
        //         'comment' => $data['comment'],
        //     ]);
        // }
    }
}
