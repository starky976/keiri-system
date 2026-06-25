<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================
    // ログイン
    // =========================================================

    public function test_正しい認証情報でログインできる(): void
    {
        $user = User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('password123'),
        ]);

        $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => 'password123',
        ])
        ->assertOk()
        ->assertJsonStructure(['user' => ['id', 'name', 'email']]);
    }

    public function test_パスワードが間違っている場合ログインできない(): void
    {
        User::factory()->create([
            'email'    => 'test@example.com',
            'password' => bcrypt('correct_password'),
        ]);

        $this->postJson('/api/login', [
            'email'    => 'test@example.com',
            'password' => 'wrong_password',
        ])
        ->assertUnprocessable() // 422
        ->assertJsonValidationErrors(['email']);
    }

    public function test_存在しないメールアドレスではログインできない(): void
    {
        $this->postJson('/api/login', [
            'email'    => 'nobody@example.com',
            'password' => 'password',
        ])
        ->assertUnprocessable();
    }

    public function test_メールアドレスなしではログインできない(): void
    {
        $this->postJson('/api/login', [
            'password' => 'password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
    }

    public function test_パスワードなしではログインできない(): void
    {
        $this->postJson('/api/login', [
            'email' => 'test@example.com',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['password']);
    }

    public function test_メール形式が不正な場合ログインできない(): void
    {
        $this->postJson('/api/login', [
            'email'    => 'not-an-email',
            'password' => 'password',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email']);
    }

    // =========================================================
    // ログアウト
    // =========================================================

    public function test_認証済みユーザーはログアウトできる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->postJson('/api/logout')
             ->assertOk()
             ->assertJsonStructure(['message']);
    }

    public function test_未認証ユーザーはログアウトできない(): void
    {
        $this->postJson('/api/logout')
             ->assertUnauthorized(); // 401
    }

    // =========================================================
    // ユーザー情報取得
    // =========================================================

    public function test_認証済みユーザーは自分の情報を取得できる(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
             ->getJson('/api/user')
             ->assertOk()
             ->assertJsonPath('user.id', $user->id)
             ->assertJsonPath('user.email', $user->email);
    }

    public function test_未認証ユーザーはユーザー情報を取得できない(): void
    {
        $this->getJson('/api/user')
             ->assertUnauthorized();
    }
}
