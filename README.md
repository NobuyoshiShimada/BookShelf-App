#  BookShelf　書籍レビューアプリ

##　環境構築
**Dockerビルド**
1. `git clone git@github.com:NobuyoshiShimada/coachtech_furima.git`
2. DockerDesktopアプリを立ち上げる
3. `docker-compose up -d --build`

> *MacのM1・M2チップのPCの場合、`no matching manifest for linux/arm64/v8 in the manifest list entries`のメッセージが表示されビルドができないことがあります。
エラーが発生する場合は、docker-compose.ymlファイルの「mysql」内に「platform」の項目を追加で記載してください*
``` bash
mysql:
    platform: linux/x86_64(この文追加)
    image: mysql:8.0.26
    environment:
```

**Laravel環境構築**
1. phpコンテナ内に入る
`docker-compose exec php bash`
2. 依存ライブラリのインストール
`composer install`
3. stripe決済の公式ライブラリをインストール
`composer require stripe/stripe-php`
4. 「.env.example」ファイルを 「.env」ファイルに命名を変更。または、新しく.envファイルを作成
5. .envに以下の環境変数を追加
``` text
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=coachtech_furima_db
DB_USERNAME=coachtech_furima_user
DB_PASSWORD=coachtech_furima_pass

# Mailpit設定
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=sample@example.com
MAIL_FROM_NAME="${APP_NAME}"

# Stripe APIキー (テスト用キーを入力してください)
STRIPE_KEY=your_stripe_publishable_key_here
STRIPE_SECRET=your_stripe_secret_key_here

```

6. アプリケーションキーの作成
``` bash
php artisan key:generate
```

7. マイグレーションの実行
``` bash
php artisan migrate
```

8. シーディングの実行
``` bash
php artisan db:seed
```

8. アップロード画像用リンクの作成
``` bash
php artisan storage:link
```


## 使用技術(実行環境)
- macOS Swquoia 15.6
- PHP8.5.3
- Laravel8.83.29
- MySQL8.0.26
- nginx 1.21.1
- Mailpit（メール擬似サーバー）
- Stripe（テスト環境決済）

## ER図
## URL
- 開発環境：http://localhost/products
- phpMyAdmin:：http://localhost:8080/
