<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClientTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function validPayload(array $override = []): array
    {
        return array_merge([
            'code'           => 'C0001',
            'name'           => '株式会社テスト',
            'type'           => 'customer',
            'payment_terms'  => 30,
            'is_active'      => true,
        ], $override);
    }

    // =========================================================
    // 一覧
    // =========================================================

    public function test_取引先一覧を取得できる(): void
    {
        Client::factory(5)->create();

        $this->actingAs($this->user)
             ->getJson('/api/clients')
             ->assertOk()
             ->assertJsonStructure(['data', 'total']);
    }

    public function test_名前でキーワード検索できる(): void
    {
        Client::factory()->create(['name' => '株式会社テスト商事']);
        Client::factory()->create(['name' => '有限会社サンプル工業']);

        $this->actingAs($this->user)
             ->getJson('/api/clients?search=テスト')
             ->assertOk()
             ->assertJsonCount(1, 'data');
    }

    public function test_種別で絞り込みができる(): void
    {
        Client::factory(2)->create(['type' => 'customer']);
        Client::factory(3)->create(['type' => 'vendor']);

        $this->actingAs($this->user)
             ->getJson('/api/clients?type=vendor')
             ->assertOk()
             ->assertJsonCount(3, 'data');
    }

    // =========================================================
    // 登録
    // =========================================================

    public function test_取引先を登録できる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/clients', $this->validPayload())
             ->assertCreated()
             ->assertJsonPath('name', '株式会社テスト')
             ->assertJsonPath('code', 'C0001');
    }

    public function test_全項目を指定して登録できる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/clients', $this->validPayload([
                 'name_kana'      => 'カブシキガイシャテスト',
                 'type'           => 'both',
                 'postal_code'    => '100-0001',
                 'address'        => '東京都千代田区1-1-1',
                 'phone'          => '03-1234-5678',
                 'email'          => 'info@test.co.jp',
                 'contact_person' => '山田 太郎',
                 'payment_terms'  => 60,
                 'notes'          => 'テスト備考',
             ]))
             ->assertCreated();
    }

    public function test_コードが重複する場合は登録できない(): void
    {
        Client::factory()->create(['code' => 'C0001']);

        $this->actingAs($this->user)
             ->postJson('/api/clients', $this->validPayload(['code' => 'C0001']))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['code']);
    }

    public function test_必須項目なしでは登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/clients', [])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['code', 'name', 'type']);
    }

    public function test_無効な種別では登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/clients', $this->validPayload(['type' => 'invalid']))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['type']);
    }

    public function test_無効なメールアドレスでは登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/clients', $this->validPayload(['email' => 'not-an-email']))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['email']);
    }

    public function test_payment_termsが180を超えると登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/clients', $this->validPayload(['payment_terms' => 181]))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['payment_terms']);
    }

    // =========================================================
    // 詳細
    // =========================================================

    public function test_取引先詳細を取得できる(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($this->user)
             ->getJson("/api/clients/{$client->id}")
             ->assertOk()
             ->assertJsonPath('id', $client->id)
             ->assertJsonStructure(['sales', 'invoices']);
    }

    public function test_存在しない取引先は404になる(): void
    {
        $this->actingAs($this->user)
             ->getJson('/api/clients/99999')
             ->assertNotFound();
    }

    // =========================================================
    // 更新
    // =========================================================

    public function test_取引先を更新できる(): void
    {
        $client = Client::factory()->create(['code' => 'C0001']);

        $this->actingAs($this->user)
             ->putJson("/api/clients/{$client->id}", $this->validPayload([
                 'name' => '更新後の会社名',
             ]))
             ->assertOk()
             ->assertJsonPath('name', '更新後の会社名');
    }

    public function test_自分自身のコードは重複チェックをパスする(): void
    {
        $client = Client::factory()->create(['code' => 'C0001']);

        // 同じコードで更新してもエラーにならない
        $this->actingAs($this->user)
             ->putJson("/api/clients/{$client->id}", $this->validPayload([
                 'code' => 'C0001', // 同じコード
                 'name' => '更新後名称',
             ]))
             ->assertOk();
    }

    // =========================================================
    // 削除
    // =========================================================

    public function test_取引先を削除できる(): void
    {
        $client = Client::factory()->create();

        $this->actingAs($this->user)
             ->deleteJson("/api/clients/{$client->id}")
             ->assertOk();

        $this->assertSoftDeleted('clients', ['id' => $client->id]);
    }

    public function test_未認証では取引先操作できない(): void
    {
        $this->getJson('/api/clients')->assertUnauthorized();
        $this->postJson('/api/clients', [])->assertUnauthorized();
    }
}
