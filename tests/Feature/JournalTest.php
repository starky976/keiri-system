<?php

namespace Tests\Feature;

use App\Models\AccountItem;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JournalTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private AccountItem $cash;
    private AccountItem $sales;
    private AccountItem $ar;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        // 勘定科目（テスト用）
        $this->cash  = AccountItem::create(['code' => '1001', 'name' => '現金',   'category' => 'asset',   'sort_order' => 1, 'is_active' => true]);
        $this->sales = AccountItem::create(['code' => '4001', 'name' => '売上高', 'category' => 'revenue', 'sort_order' => 2, 'is_active' => true]);
        $this->ar    = AccountItem::create(['code' => '1201', 'name' => '売掛金', 'category' => 'asset',   'sort_order' => 3, 'is_active' => true]);
    }

    // =========================================================
    // 一覧
    // =========================================================

    public function test_仕訳一覧を取得できる(): void
    {
        Journal::factory(3)->create();

        $this->actingAs($this->user)
             ->getJson('/api/journals')
             ->assertOk()
             ->assertJsonStructure(['data', 'total', 'per_page']);
    }

    public function test_未認証では仕訳一覧を取得できない(): void
    {
        $this->getJson('/api/journals')->assertUnauthorized();
    }

    // =========================================================
    // 登録（正常系）
    // =========================================================

    public function test_貸借が一致する仕訳を登録できる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/journals', [
                 'journal_date' => '2026-06-01',
                 'description'  => '売上計上',
                 'entries' => [
                     ['side' => 'debit',  'account_item_id' => $this->ar->id,    'amount' => 110000, 'description' => '売掛金'],
                     ['side' => 'credit', 'account_item_id' => $this->sales->id, 'amount' => 100000, 'description' => '売上高'],
                     ['side' => 'credit', 'account_item_id' => $this->cash->id,  'amount' => 10000,  'description' => '仮受消費税'],
                 ],
             ])
             ->assertCreated()
             ->assertJsonStructure(['id', 'journal_number', 'entries']);
    }

    public function test_複合仕訳（借方複数）を登録できる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/journals', [
                 'journal_date' => '2026-06-01',
                 'description'  => '複合仕訳テスト',
                 'entries' => [
                     ['side' => 'debit',  'account_item_id' => $this->cash->id,  'amount' => 50000],
                     ['side' => 'debit',  'account_item_id' => $this->ar->id,    'amount' => 50000],
                     ['side' => 'credit', 'account_item_id' => $this->sales->id, 'amount' => 100000],
                 ],
             ])
             ->assertCreated();
    }

    // =========================================================
    // 登録（異常系）- 貸借不一致
    // =========================================================

    public function test_貸借が不一致の場合422エラーになる(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/journals', [
                 'journal_date' => '2026-06-01',
                 'description'  => '不一致テスト',
                 'entries' => [
                     ['side' => 'debit',  'account_item_id' => $this->ar->id,    'amount' => 100000],
                     ['side' => 'credit', 'account_item_id' => $this->sales->id, 'amount' => 99999], // 1円ずれ
                 ],
             ])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['entries']);
    }

    public function test_借方のみでは登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/journals', [
                 'journal_date' => '2026-06-01',
                 'description'  => '借方のみ',
                 'entries' => [
                     ['side' => 'debit', 'account_item_id' => $this->cash->id, 'amount' => 10000],
                 ],
             ])
             ->assertUnprocessable();
    }

    public function test_仕訳行が1行では登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/journals', [
                 'journal_date' => '2026-06-01',
                 'description'  => '1行仕訳',
                 'entries'      => [
                     ['side' => 'debit', 'account_item_id' => $this->cash->id, 'amount' => 10000],
                 ],
             ])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['entries']);
    }

    public function test_金額が0では登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/journals', [
                 'journal_date' => '2026-06-01',
                 'description'  => '金額0テスト',
                 'entries' => [
                     ['side' => 'debit',  'account_item_id' => $this->cash->id,  'amount' => 0],
                     ['side' => 'credit', 'account_item_id' => $this->sales->id, 'amount' => 0],
                 ],
             ])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['entries.0.amount', 'entries.1.amount']);
    }

    public function test_存在しない勘定科目IDでは登録できない(): void
    {
        $this->actingAs($this->user)
             ->postJson('/api/journals', [
                 'journal_date' => '2026-06-01',
                 'description'  => '無効勘定科目',
                 'entries' => [
                     ['side' => 'debit',  'account_item_id' => 99999, 'amount' => 10000],
                     ['side' => 'credit', 'account_item_id' => 99998, 'amount' => 10000],
                 ],
             ])
             ->assertUnprocessable()
             ->assertJsonValidationErrors(['entries.0.account_item_id']);
    }

    // =========================================================
    // 詳細・更新・削除
    // =========================================================

    public function test_仕訳詳細を取得できる(): void
    {
        $journal = Journal::factory()->create();

        $this->actingAs($this->user)
             ->getJson("/api/journals/{$journal->id}")
             ->assertOk()
             ->assertJsonPath('id', $journal->id);
    }

    public function test_存在しない仕訳は404になる(): void
    {
        $this->actingAs($this->user)
             ->getJson('/api/journals/99999')
             ->assertNotFound();
    }

    public function test_仕訳を更新できる(): void
    {
        $journal = Journal::factory()->hasEntries(2)->create();

        $this->actingAs($this->user)
             ->putJson("/api/journals/{$journal->id}", [
                 'journal_date' => '2026-07-01',
                 'description'  => '更新後の摘要',
                 'entries' => [
                     ['side' => 'debit',  'account_item_id' => $this->cash->id,  'amount' => 50000],
                     ['side' => 'credit', 'account_item_id' => $this->sales->id, 'amount' => 50000],
                 ],
             ])
             ->assertOk()
             ->assertJsonPath('description', '更新後の摘要');
    }

    public function test_仕訳を削除できる(): void
    {
        $journal = Journal::factory()->create();

        $this->actingAs($this->user)
             ->deleteJson("/api/journals/{$journal->id}")
             ->assertOk();

        $this->assertDatabaseMissing('journals', ['id' => $journal->id]);
    }
}
