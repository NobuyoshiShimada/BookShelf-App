<?php

namespace Tests\Feature\web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_ログイン画面の表示(): void
    {
        // ログイン画面を表示し、ステータス表示
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

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
        $response->assertRedirect('/books');
    }

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

    public function test_新規ユーザー登録画面が正常に表示される(): void
    {
        // 新規登録画面の表示
        $response = $this->get('/register');

        // ステータスを表示
        $response->assertStatus(200);
    }

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
        $response->assertRedirect();
    }

    public function test_ログアウトできる(): void
    {
        // テスト用ユーザーを1人作成
        $user = User::factory()->create();

        // ログイン状態からログアウト
        $response = $this->actingAs($user)->post('/logout');

        // 未ログイン状態になっているかテスト
        $this->assertGuest();
        // リダイレクト先をテスト
        $response->assertRedirect('/books');
    }
}
