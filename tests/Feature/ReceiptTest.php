<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Receipt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReceiptTest extends TestCase
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

    // =========================================================
    // 一覧
    // =========================================================

    public function test_入金一覧を取得できる(): void
    {
        Receipt::factory(3)->create();

        $this->actingAs($this->user)
             ->getJson('/api/receipts')
             ->assertOk()
             ->assertJsonStructure(['data', 'total']);
    }

    // =========================================================
    // 請求書消込ロジック
    // =========================================================

    public function test_入金登録で請求書のpaid_amountが加算される(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id'    => $this->client->id,
            'total_amount' => 110000,
            'paid_amount'  => 0,
            'status'       => 'sent',
        ]);

        $this->actingAs($this->user)
             ->postJson('/api/receipts', [
                 'client_id'    => $this->client->id,
                 'invoice_id'   => $invoice->id,
                 'receipt_date' => '2026-06-01',
                 'amount'       => 60000,
                 'method'       => 'bank_transfer',
             ])
             ->assertCreated();

        // paid_amount が加算されているか
        $this->assertDatabaseHas('invoices', [
            'id'          => $invoice->id,
            'paid_amount' => 60000,
            'status'      => 'sent', // まだ全額ではない
        ]);
    }

    public function test_入金額が合計に達したら請求書がpaid状態になる(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id'    => $this->client->id,
            'total_amount' => 110000,
            'paid_amount'  => 0,
            'status'       => 'sent',
        ]);

        $this->actingAs($this->user)
             ->postJson('/api/receipts', [
                 'client_id'    => $this->client->id,
                 'invoice_id'   => $invoice->id,
                 'receipt_date' => '2026-06-01',
                 'amount'       => 110000, // 全額
                 'method'       => 'bank_transfer',
             ])
             ->assertCreated();

        // status が paid に変わっているか
        $this->assertDatabaseHas('invoices', [
            'id'          => $invoice->id,
            'paid_amount' => 110000,
            'status'      => 'paid',
        ]);
    }

    public function test_入金額が合計を超えた場合も請求書がpaid状態になる(): void
    {
        $invoice = Invoice::factory()->create([
            'client_id'    => $this->client->id,
            'total_amount' => 110000,
            'paid_amount'  => 0,
            'status'       => 'sent',
        ]);

        $this->actingAs($this->user)
             ->postJson('/api/receipts', [
                 'client_id'    => $this->client->id,
                 'invoice_id'   => $invoice->id,
                 'receipt_date' => '2026-06-01',
                 'amount'       => 120000, // 過払い
                 'method'       => 'bank_transfer',
             ])
             ->assertCreated();

        $this->assertDatabaseHas('invoices', [
            'id'     => $invoice->id,
            'status' => 'paid',
        ]);
    }

    public function test_invoice_idなしでも入金登録できる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/receipts', [
                 'client_id'    => $this->client->id,
                 'receipt_date' => '2026-06-01',
                 'amount'       => 50000,
                 'method'       => 'cash',
             ])
             ->assertCreated();
    }

    // =========================================================
    // バリデーション
    // =========================================================

    public function test_金額が0以下では登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/receipts', [
                 'client_id'    => $this->client->id,
                 'receipt_date' => '2026-06-01',
                 'amount'       => 0,
                 'method'       => 'bank_transfer',
             ])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['amount']);
    }

    public function test_無効なinvoice_idでは登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/receipts', [
                 'client_id'    => $this->client->id,
                 'invoice_id'   => 99999,
                 'receipt_date' => '2026-06-01',
                 'amount'       => 10000,
                 'method'       => 'bank_transfer',
             ])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['invoice_id']);
    }

    public function test_無効な入金方法では登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/receipts', [
                 'client_id'    => $this->client->id,
                 'receipt_date' => '2026-06-01',
                 'amount'       => 10000,
                 'method'       => 'invalid_method',
             ])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['method']);
    }

    // =========================================================
    // 詳細・削除
    // =========================================================

    public function test_入金詳細を取得できる(): void
    {
        $receipt = Receipt::factory()->create();

        $this->actingAs($this->user)
             ->getJson("/api/receipts/{$receipt->id}")
             ->assertOk()
             ->assertJsonPath('id', $receipt->id);
    }

    public function test_入金を削除できる(): void
    {
        $receipt = Receipt::factory()->create();

        $this->actingAs($this->user)
             ->deleteJson("/api/receipts/{$receipt->id}")
             ->assertOk();

        $this->assertDatabaseMissing('receipts', ['id' => $receipt->id]);
    }

    public function test_未認証では入金操作できない(): void
    {
        $this->getJson('/api/receipts')->assertUnauthorized();
        $this->postJson('/api/receipts', [])->assertUnauthorized();
    }
}
