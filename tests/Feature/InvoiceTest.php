<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceTest extends TestCase
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
            'client_id'    => $this->client->id,
            'invoice_date' => '2026-06-01',
            'due_date'     => '2026-06-30',
            'items'        => [
                [
                    'item_name'  => 'サービス費',
                    'unit_price' => 100000,
                    'quantity'   => 1,
                    'tax_rate'   => 10,
                ],
            ],
        ], $override);
    }

    // =========================================================
    // 一覧
    // =========================================================

    public function test_請求書一覧を取得できる(): void
    {
        Invoice::factory(3)->create();

        $this->actingAs($this->user)
             ->getJson('/api/invoices')
             ->assertOk()
             ->assertJsonStructure(['data', 'total']);
    }

    public function test_ステータスで請求書を絞り込める(): void
    {
        Invoice::factory(2)->create(['status' => 'draft']);
        Invoice::factory(3)->create(['status' => 'sent']);

        $this->actingAs($this->user)
             ->getJson('/api/invoices?status=sent')
             ->assertOk()
             ->assertJsonCount(3, 'data');
    }

    // =========================================================
    // 登録
    // =========================================================

    public function test_請求書を登録できる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/invoices', $this->validPayload())
             ->assertCreated()
             ->assertJsonStructure(['id', 'invoice_number', 'total_amount', 'status'])
             ->assertJsonPath('status', 'draft');
    }

    public function test_明細ごとに異なる税率を設定できる(): void
    {
        $response = $this->actingAs($this->user)
             ->postJson('/api/invoices', $this->validPayload([
                 'items' => [
                     ['item_name' => '標準税率商品', 'unit_price' => 100000, 'quantity' => 1, 'tax_rate' => 10],
                     ['item_name' => '軽減税率商品', 'unit_price' => 50000,  'quantity' => 1, 'tax_rate' => 8],
                 ],
             ]))
             ->assertCreated();

        // 小計は合計（税抜き）
        $response->assertJsonPath('subtotal', 150000);
    }

    public function test_itemsがなければ登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/invoices', $this->validPayload(['items' => []]))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['items']);
    }

    public function test_明細の税率が不正では登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/invoices', $this->validPayload([
                 'items' => [['item_name' => '商品', 'unit_price' => 10000, 'quantity' => 1, 'tax_rate' => 5]],
             ]))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['items.0.tax_rate']);
    }

    // =========================================================
    // 送付
    // =========================================================

    public function test_請求書を送付するとsentステータスになる(): void
    {
        $invoice = Invoice::factory()->create(['status' => 'draft']);

        $this->actingAs($this->user)
             ->postJson("/api/invoices/{$invoice->id}/send")
             ->assertOk()
             ->assertJsonPath('status', 'sent');

        $this->assertDatabaseHas('invoices', [
            'id'     => $invoice->id,
            'status' => 'sent',
        ]);
        $this->assertNotNull(Invoice::find($invoice->id)->sent_at);
    }

    // =========================================================
    // 詳細・更新・削除
    // =========================================================

    public function test_請求書詳細を取得できる(): void
    {
        $invoice = Invoice::factory()->create();

        $this->actingAs($this->user)
             ->getJson("/api/invoices/{$invoice->id}")
             ->assertOk()
             ->assertJsonPath('id', $invoice->id)
             ->assertJsonStructure(['items', 'receipts']);
    }

    public function test_請求書を更新できる(): void
    {
        $invoice = Invoice::factory()->create(['client_id' => $this->client->id]);

        $this->actingAs($this->user)
             ->putJson("/api/invoices/{$invoice->id}", $this->validPayload([
                 'due_date' => '2026-07-31',
             ]))
             ->assertOk()
             ->assertJsonPath('due_date', '2026-07-31');
    }

    public function test_請求書を削除できる(): void
    {
        $invoice = Invoice::factory()->create();

        $this->actingAs($this->user)
             ->deleteJson("/api/invoices/{$invoice->id}")
             ->assertOk();

        $this->assertSoftDeleted('invoices', ['id' => $invoice->id]);
    }

    public function test_未認証では請求書操作できない(): void
    {
        $this->getJson('/api/invoices')->assertUnauthorized();
    }
}
