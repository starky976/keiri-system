<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user   = User::factory()->create();
        $this->client = Client::factory()->create();
    }

    private function validPayload(array $override = []): array
    {
        return array_merge([
            'client_id'   => $this->client->id,
            'sale_date'   => '2026-06-01',
            'description' => 'テスト売上',
            'tax_rate'    => 10,
            'items'       => [
                [
                    'item_name'  => '開発費',
                    'unit_price' => 100000,
                    'quantity'   => 1,
                    'unit'       => '式',
                ],
            ],
        ], $override);
    }

    // =========================================================
    // 一覧
    // =========================================================

    public function test_売上一覧を取得できる(): void
    {
        Sale::factory(5)->create();

        $this->actingAs($this->user)
             ->getJson('/api/sales')
             ->assertOk()
             ->assertJsonStructure(['data', 'total', 'per_page', 'current_page']);
    }

    public function test_キーワードで売上を検索できる(): void
    {
        Sale::factory()->create(['description' => 'システム開発費']);
        Sale::factory()->create(['description' => 'コンサルティング費']);

        $this->actingAs($this->user)
             ->getJson('/api/sales?search=システム')
             ->assertOk()
             ->assertJsonCount(1, 'data');
    }

    public function test_ステータスで売上を絞り込める(): void
    {
        Sale::factory(2)->create(['status' => 'pending']);
        Sale::factory(3)->create(['status' => 'paid']);

        $this->actingAs($this->user)
             ->getJson('/api/sales?status=pending')
             ->assertOk()
             ->assertJsonCount(2, 'data');
    }

    public function test_未認証では売上一覧を取得できない(): void
    {
        $this->getJson('/api/sales')->assertUnauthorized();
    }

    // =========================================================
    // 登録（正常系）
    // =========================================================

    public function test_売上を登録できる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/sales', $this->validPayload())
             ->assertCreated()
             ->assertJsonStructure(['id', 'sale_number', 'subtotal', 'tax_amount', 'total_amount', 'items']);
    }

    public function test_税込合計が正しく計算される(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/sales', $this->validPayload([
                 'tax_rate' => 10,
                 'items'    => [['item_name' => '商品A', 'unit_price' => 100000, 'quantity' => 1]],
             ]))
             ->assertCreated()
             ->assertJsonPath('subtotal', 100000)
             ->assertJsonPath('tax_amount', 10000)
             ->assertJsonPath('total_amount', 110000);
    }

    public function test_税率0の場合税額は0になる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/sales', $this->validPayload([
                 'tax_rate' => 0,
                 'items'    => [['item_name' => '非課税商品', 'unit_price' => 50000, 'quantity' => 1]],
             ]))
             ->assertCreated()
             ->assertJsonPath('tax_amount', 0)
             ->assertJsonPath('total_amount', 50000);
    }

    public function test_複数明細の合計が正しく計算される(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/sales', $this->validPayload([
                 'tax_rate' => 10,
                 'items'    => [
                     ['item_name' => '商品A', 'unit_price' => 50000, 'quantity' => 2],
                     ['item_name' => '商品B', 'unit_price' => 30000, 'quantity' => 3],
                 ],
             ]))
             ->assertCreated()
             ->assertJsonPath('subtotal', 190000)   // 50000×2 + 30000×3
             ->assertJsonPath('total_amount', 209000); // 190000 × 1.1
    }

    // =========================================================
    // 登録（異常系）
    // =========================================================

    public function test_存在しないclient_idでは登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/sales', $this->validPayload(['client_id' => 99999]))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['client_id']);
    }

    public function test_itemsが空では登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/sales', $this->validPayload(['items' => []]))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['items']);
    }

    public function test_無効な税率では登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/sales', $this->validPayload(['tax_rate' => 5]))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['tax_rate']);
    }

    public function test_単価が負の値では登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/sales', $this->validPayload([
                 'items' => [['item_name' => '商品', 'unit_price' => -1000, 'quantity' => 1]],
             ]))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['items.0.unit_price']);
    }

    // =========================================================
    // 詳細・更新・削除
    // =========================================================

    public function test_売上詳細を取得できる(): void
    {
        $sale = Sale::factory()->create();

        $this->actingAs($this->user)
             ->getJson("/api/sales/{$sale->id}")
             ->assertOk()
             ->assertJsonPath('id', $sale->id);
    }

    public function test_存在しない売上は404になる(): void
    {
        $this->actingAs($this->user)
             ->getJson('/api/sales/99999')
             ->assertNotFound();
    }

    public function test_売上を更新できる(): void
    {
        $sale = Sale::factory()->create(['client_id' => $this->client->id]);

        $this->actingAs($this->user)
             ->putJson("/api/sales/{$sale->id}", $this->validPayload([
                 'description' => '更新後の件名',
             ]))
             ->assertOk()
             ->assertJsonPath('description', '更新後の件名');
    }

    public function test_売上を削除できる(): void
    {
        $sale = Sale::factory()->create();

        $this->actingAs($this->user)
             ->deleteJson("/api/sales/{$sale->id}")
             ->assertOk();

        $this->assertSoftDeleted('sales', ['id' => $sale->id]);
    }
}
