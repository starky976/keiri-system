<?php

namespace Database\Factories;

use App\Models\AccountItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JournalFactory extends Factory
{
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-6 months', 'now');

        return [
            'journal_number' => 'J' . $date->format('Ymd') . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'journal_date'   => $date->format('Y-m-d'),
            'description'    => fake()->randomElement([
                '売上計上', '仕入計上', '給与支払', '地代家賃支払', '水道光熱費支払', '経費精算',
            ]),
            'source_type'    => 'manual',
            'source_id'      => null,
            'user_id'        => User::inRandomOrder()->value('id') ?? User::factory(),
        ];
    }

    /**
     * 仕訳明細を2行（借方・貸方）付きで生成するstate
     */
    public function hasEntries(int $count = 2): static
    {
        return $this->afterCreating(function ($journal) {
            // 勘定科目を2件取得または生成
            $debitItem  = AccountItem::inRandomOrder()->first()
                ?? AccountItem::create(['code' => '1001', 'name' => '現金',   'category' => 'asset',   'sort_order' => 1, 'is_active' => true]);
            $creditItem = AccountItem::inRandomOrder()->first()
                ?? AccountItem::create(['code' => '4001', 'name' => '売上高', 'category' => 'revenue', 'sort_order' => 2, 'is_active' => true]);

            $amount = fake()->numberBetween(10000, 500000);

            $journal->entries()->create([
                'side'            => 'debit',
                'account_item_id' => $debitItem->id,
                'amount'          => $amount,
                'description'     => '借方テスト',
                'sort_order'      => 0,
            ]);
            $journal->entries()->create([
                'side'            => 'credit',
                'account_item_id' => $creditItem->id,
                'amount'          => $amount,
                'description'     => '貸方テスト',
                'sort_order'      => 1,
            ]);
        });
    }
}
