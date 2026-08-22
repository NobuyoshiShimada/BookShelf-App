<?php

namespace Tests\Feature\Api\V1;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * API認証機能（ログイン・ログアウト）
 */
class AdvancedAuthApiTest extends TestCase
{
    use RefreshDatabase;

    private string $loginUrl = '/api/v1/login';

    private string $logoutUrl = '/api/v1/logout';

    /**
     * 正しいメールアドレスとパスワードが送信された場合、
     * データベースにトークンが記録され、プレーンテキストトークンが返却されることを検証
     */
    public function test_正しい資格情報でログインすると_ap_iトークンが発行される(): void
    {
        // パスワードを明示的にハッシュ化してユーザーを作成
        $user = User::factory()->create([
            'email' => 'api-test@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->postJson($this->loginUrl, [
            'email' => 'api-test@example.com',
            'password' => 'secret-password',
            'device_name' => 'Postman_Client',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['token']);

        // データベース（personal_access_tokens）にトークンが物理的に作られているかアサート
        $this->assertCount(1, $user->fresh()->tokens);
        $this->assertEquals('Postman_Client', $user->fresh()->tokens->first()->name);
    }

    /**
     * パスワード、またはメールアドレスが登録データと一致しない場合、
     * トークンを発行せず、422 ValidationException（ログイン情報不正）を返却することを検証
     */
    public function test_パスワードが間違っている場合は認証が拒否される(): void
    {
        $user = User::factory()->create([
            'email' => 'api-test@example.com',
            'password' => Hash::make('secret-password'),
        ]);

        $response = $this->postJson($this->loginUrl, [
            'email' => 'api-test@example.com',
            'password' => 'wrong-password', // 間違ったパスワード
            'device_name' => 'Postman_Client',
        ]);

        // コントローラー内の ValidationException に合わせて 422 をアサート
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);

        // トークンが発行されていない（0件である）ことを厳密に検証
        $this->assertCount(0, $user->fresh()->tokens);
    }

    /**
     * ログイン状態のユーザー（Bearer Token保持）がログアウトを要請した場合、
     * サーバー側で該当トークンが物理削除され、以降はそのトークンが使えなくなることを検証
     */
    public function test_ログイン中のユーザーは正常にログアウトしてトークンを失効できる(): void
    {
        $user = User::factory()->create();

        // Sanctumの機能で擬似的にトークンを発行
        $token = $user->createToken('Test_Device')->plainTextToken;
        $this->assertCount(1, $user->fresh()->tokens); // 最初は1件存在する

        // Authorization ヘッダーに Bearer トークンを載せてログアウトAPIを叩く
        $response = $this->withHeaders([
            'Authorization' => 'Bearer '.$token,
        ])->postJson($this->logoutUrl);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'ログアウトしました（トークンを失効しました）。']);

        // データベース上からトークンレコードが完全に消去（0件）されたか検証
        $this->assertCount(0, $user->fresh()->tokens);
    }
}
