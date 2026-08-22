<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ログイン画面の初期表示
     *
     * 未ログイン状態のゲストユーザーがログイン用 URL（/login）にアクセスした際、
     * 認証用の HTML 画面が正常（200）にレスポンスされるかを検証。
     */
    public function test_ログイン画面の表示(): void
    {
        // ログイン画面を表示し、ステータス表示
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    /**
     * 正しい資格情報によるログイン成功処理
     *
     * DB に実在するユーザーの本物のメールアドレスおよびパスワードを送信した際、
     * セッション認証が正常に通過して該当ユーザーとしてログイン状態になり、トップ画面へリダイレクトされるかを検証。
     */
    public function test_正しい情報でログインできるかテスト(): void
    {
        // テスト用にユーザーを1人作成（パスワードをハッシュ化）
        $user = User::factory()->create([
            'password' => Hash::make($password = 'password123'),
        ]);

        // メール、パスワードを入力してログイン
        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => $password,
        ]);

        // ログインできているかテスト
        $this->assertAuthenticatedAs($user);
        // リダイレクト先のテスト
        $response->assertRedirect('/');
    }

    /**
     * 誤ったパスワードによるログイン拒否
     *
     * 登録済みのメールアドレスに対して不正なパスワードを送信してログインを試みた際、
     * セッション認証システムによって安全にブロックされ、未ログイン状態（Guest）が維持されるかを検証。
     */
    public function test_間違ったパスワードではログインできない(): void
    {
        // テスト用ユーザー作成
        $user = User::factory()->create();

        // メール、間違ったパスワードを入力してログイン
        $this->post('/login', [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        // 未ログインの状態かテスト
        $this->assertGuest();
    }

    /**
     * 新規ユーザー登録画面の初期表示
     *
     * 一般ゲストユーザーが会員登録用 URL（/register）にアクセスした際、
     * 入力フォームを伴う画面が正常（200）にレンダリングされるかを検証。
     */
    public function test_新規ユーザー登録画面が正常に表示される(): void
    {
        // 新規登録画面の表示
        $response = $this->get('/register');

        // ステータスを表示
        $response->assertStatus(200);
    }

    /**
     * ユーザーの新規会員登録および自動ログイン処理
     *
     * 必要な登録情報（パスワード確認一致を含む）を正常に送信した際、
     * レコードが `users` テーブルへ安全に保存（永続化）され、かつ
     * 新規作成アカウントとして自動的にセッションログインが完了してリダイレクトされるかを検証。
     */
    public function test_新規登録が正常にできる(): void
    {
        // テスト用ユーザー新規登録内容
        $response = $this->post('/register', [
            'name' => 'テストユーザー',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        // ログインしたかテスト
        $this->assertAuthenticated();

        // データベースにメール情報が保存されたかテスト
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);

        // リダイレクト先をテスト
        $response->assertRedirect('/');
    }

    /**
     * セッションログアウトの実行
     *
     * ログイン中のユーザーがログアウトエンドポイント（/logout）に POST リクエストを送信した際、
     * サーバー側のセッションおよびブラウザ側の認証状態が安全に破棄され、未ログインのゲスト状態へ遷移するかを検証。
     */
    public function test_ログアウトできる(): void
    {
        // テスト用ユーザーを1人作成
        $user = User::factory()->create();

        // ログイン状態からログアウト
        $response = $this->actingAs($user)->post('/logout');

        // 未ログイン状態になっているかテスト
        $this->assertGuest();
        // リダイレクト先をテスト
        $response->assertRedirect('/');
    }
}
