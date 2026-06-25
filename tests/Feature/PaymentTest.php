<?php

namespace Tests\Feature;

use App\Models\AccountItem;
use App\Models\Client;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Client $client;
    private AccountItem $accountItem;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user   = User::factory()->create();
        $this->client = Client::factory()->create(['type' => 'vendor']);
        $this->accountItem = AccountItem::create([
            'code'       => '2001',
            'name'       => '買掛金',
            'category'   => 'liability',
            'sort_order' => 1,
            'is_active'  => true,
        ]);
    }

    private function validPayload(array $override = []): array
    {
        return array_merge([
            'client_id'       => $this->client->id,
            'due_date'        => '2026-06-30',
            'amount'          => 100000,
            'method'          => 'bank_transfer',
            'description'     => '外注費（6月分）',
            'account_item_id' => $this->accountItem->id,
        ], $override);
    }

    // =========================================================
    // 一覧
    // =========================================================

    public function test_支払一覧を取得できる(): void
    {
        Payment::factory(3)->create();

        $this->actingAs($this->user)
             ->getJson('/api/payments')
             ->assertOk()
             ->assertJsonStructure(['data', 'total']);
    }

    public function test_ステータスで支払を絞り込める(): void
    {
        Payment::factory(2)->create(['status' => 'pending']);
        Payment::factory(3)->create(['status' => 'paid']);

        $this->actingAs($this->user)
             ->getJson('/api/payments?status=pending')
             ->assertOk()
             ->assertJsonCount(2, 'data');
    }

    // =========================================================
    // 登録
    // =========================================================

    public function test_支払を登録できる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/payments', $this->validPayload())
             ->assertCreated()
             ->assertJsonStructure(['id', 'payment_number', 'status'])
             ->assertJsonPath('status', 'pending');
    }

    public function test_支払日を指定して登録できる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/payments', $this->validPayload([
                 'payment_date' => '2026-06-28',
             ]))
             ->assertCreated()
             ->assertJsonPath('payment_date', '2026-06-28');
    }

    public function test_金額が0以下では登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/payments', $this->validPayload(['amount' => 0]))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['amount']);
    }

    public function test_無効な支払方法では登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/payments', $this->validPayload(['method' => 'invalid']))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['method']);
    }

    public function test_存在しない勘定科目IDでは登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/payments', $this->validPayload(['account_item_id' => 99999]))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['account_item_id']);
    }

    public function test_必須項目なしでは登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/payments', [])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['client_id', 'due_date', 'amount', 'method', 'description', 'account_item_id']);
    }

    // =========================================================
    // 詳細・更新・削除
    // =========================================================

    public function test_支払詳細を取得できる(): void
    {
        $payment = Payment::factory()->create();

        $this->actingAs($this->user)
             ->getJson("/api/payments/{$payment->id}")
             ->assertOk()
             ->assertJsonPath('id', $payment->id)
             ->assertJsonStructure(['client', 'accountItem']);
    }

    public function test_存在しない支払は404になる(): void
    {
        $this->actingAs($this->user)
             ->getJson('/api/payments/99999')
             ->assertNotFound();
    }

    public function test_支払ステータスを更新できる(): void
    {
        $payment = Payment::factory()->create([
            'client_id'       => $this->client->id,
            'account_item_id' => $this->accountItem->id,
            'status'          => 'pending',
        ]);

        $this->actingAs($this->user)
             ->putJson("/api/payments/{$payment->id}", $this->validPayload([
                 'status'       => 'paid',
                 'payment_date' => '2026-06-28',
             ]))
             ->assertOk()
             ->assertJsonPath('status', 'paid');
    }

    public function test_無効なステータスでは更新できない(): void
    {
        $payment = Payment::factory()->create([
            'client_id'       => $this->client->id,
            'account_item_id' => $this->accountItem->id,
        ]);

        $this->actingAs($this->user)
             ->putJson("/api/payments/{$payment->id}", $this->validPayload(['status' => 'invalid_status']))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['status']);
    }

    public function test_支払を削除できる(): void
    {
        $payment = Payment::factory()->create();

        $this->actingAs($this->user)
             ->deleteJson("/api/payments/{$payment->id}")
             ->assertOk();

        $this->assertDatabaseMissing('payments', ['id' => $payment->id]);
    }

    public function test_未認証では支払操作できない(): void
    {
        $this->getJson('/api/payments')->assertUnauthorized();
        $this->postJson('/api/payments', [])->assertUnauthorized();
    }
}
