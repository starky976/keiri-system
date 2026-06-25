<?php

namespace Tests\Feature;

use App\Models\AccountItem;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $approver;
    private AccountItem $transport;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user     = User::factory()->create();
        $this->approver = User::factory()->create();
        $this->transport = AccountItem::create([
            'code'       => '5501',
            'name'       => '旅費交通費',
            'category'   => 'expense',
            'sort_order' => 1,
            'is_active'  => true,
        ]);
    }

    private function validPayload(array $override = []): array
    {
        return array_merge([
            'expense_date' => '2026-06-01',
            'title'        => '出張費精算',
            'items'        => [
                [
                    'account_item_id' => $this->transport->id,
                    'item_date'       => '2026-06-01',
                    'description'     => '電車代',
                    'amount'          => 580,
                    'tax_rate'        => 10,
                ],
            ],
        ], $override);
    }

    // =========================================================
    // 一覧
    // =========================================================

    public function test_経費一覧を取得できる(): void
    {
        Expense::factory(3)->create();

        $this->actingAs($this->user)
             ->getJson('/api/expenses')
             ->assertOk()
             ->assertJsonStructure(['data', 'total']);
    }

    public function test_ステータスで経費を絞り込める(): void
    {
        Expense::factory(2)->create(['status' => 'pending']);
        Expense::factory(3)->create(['status' => 'approved']);

        $this->actingAs($this->user)
             ->getJson('/api/expenses?status=pending')
             ->assertOk()
             ->assertJsonCount(2, 'data');
    }

    // =========================================================
    // 申請（登録）
    // =========================================================

    public function test_経費申請を登録できる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/expenses', $this->validPayload())
             ->assertCreated()
             ->assertJsonStructure(['id', 'expense_number', 'total_amount', 'status'])
             ->assertJsonPath('status', 'pending');
    }

    public function test_申請日は自動で今日の日付になる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/expenses', $this->validPayload())
             ->assertCreated()
             ->assertJsonPath('applied_date', now()->format('Y-m-d'));
    }

    public function test_合計金額が明細の合計になる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/expenses', $this->validPayload([
                 'items' => [
                     ['account_item_id' => $this->transport->id, 'item_date' => '2026-06-01', 'description' => '電車代', 'amount' => 580,   'tax_rate' => 10],
                     ['account_item_id' => $this->transport->id, 'item_date' => '2026-06-02', 'description' => 'タクシー', 'amount' => 3200, 'tax_rate' => 10],
                 ],
             ]))
             ->assertCreated()
             ->assertJsonPath('total_amount', 3780);
    }

    public function test_明細なしでは申請できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/expenses', $this->validPayload(['items' => []]))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['items']);
    }

    public function test_存在しない勘定科目IDでは申請できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/expenses', $this->validPayload([
                 'items' => [
                     ['account_item_id' => 99999, 'item_date' => '2026-06-01', 'description' => 'テスト', 'amount' => 1000, 'tax_rate' => 10],
                 ],
             ]))
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['items.0.account_item_id']);
    }

    // =========================================================
    // 承認ワークフロー
    // =========================================================

    public function test_経費を承認できる(): void
    {
        $expense = Expense::factory()->create(['status' => 'pending']);

        $this->actingAs($this->approver)
             ->postJson("/api/expenses/{$expense->id}/approve")
             ->assertOk()
             ->assertJsonPath('status', 'approved');

        $this->assertDatabaseHas('expenses', [
            'id'          => $expense->id,
            'status'      => 'approved',
            'approved_by' => $this->approver->id,
        ]);
        $this->assertNotNull(Expense::find($expense->id)->approved_at);
    }

    public function test_経費を却下できる(): void
    {
        $expense = Expense::factory()->create(['status' => 'pending']);

        $this->actingAs($this->approver)
             ->postJson("/api/expenses/{$expense->id}/reject", [
                 'reason' => '領収書が添付されていません。',
             ])
             ->assertOk()
             ->assertJsonPath('status', 'rejected');

        $this->assertDatabaseHas('expenses', [
            'id'               => $expense->id,
            'status'           => 'rejected',
            'rejection_reason' => '領収書が添付されていません。',
        ]);
    }

    public function test_却下理由なしでは却下できない(): void
    {
        $expense = Expense::factory()->create(['status' => 'pending']);

        $this->actingAs($this->approver)
             ->postJson("/api/expenses/{$expense->id}/reject", [])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['reason']);
    }

    public function test_却下理由が500文字を超えると却下できない(): void
    {
        $expense = Expense::factory()->create(['status' => 'pending']);

        $this->actingAs($this->approver)
             ->postJson("/api/expenses/{$expense->id}/reject", [
                 'reason' => str_repeat('あ', 501),
             ])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['reason']);
    }

    // =========================================================
    // 詳細・更新・削除
    // =========================================================

    public function test_経費詳細を取得できる(): void
    {
        $expense = Expense::factory()->create();

        $this->actingAs($this->user)
             ->getJson("/api/expenses/{$expense->id}")
             ->assertOk()
             ->assertJsonPath('id', $expense->id)
             ->assertJsonStructure(['items', 'user', 'approver']);
    }

    public function test_経費を削除できる(): void
    {
        $expense = Expense::factory()->create();

        $this->actingAs($this->user)
             ->deleteJson("/api/expenses/{$expense->id}")
             ->assertOk();

        $this->assertSoftDeleted('expenses', ['id' => $expense->id]);
    }

    public function test_未認証では経費操作できない(): void
    {
        $this->getJson('/api/expenses')->assertUnauthorized();
        $this->postJson('/api/expenses', [])->assertUnauthorized();
    }
}
