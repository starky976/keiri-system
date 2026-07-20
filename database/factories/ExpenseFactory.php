<?php
namespace Database\Factories;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(['draft','pending','approved','rejected']);
        $appliedDate = fake()->dateTimeBetween('-3 months','now');
        return [
            'expense_number'   => 'EXP-' . fake()->unique()->numerify('######'),
            'user_id'          => User::inRandomOrder()->value('id') ?? User::factory()->create()->id,
            'approved_by'      => in_array($status,['approved','rejected']) ? (User::inRandomOrder()->value('id') ?? User::factory()->create()->id) : null,
            'expense_date'     => $appliedDate->format('Y-m-d'),
            'applied_date'     => $appliedDate->format('Y-m-d'),
            'title'            => fake()->randomElement(['1月交通費精算','2月出張費','接待交際費精算','消耗品購入','研修参加費']),
            'total_amount'     => 0,
            'status'           => $status,
            'approved_at'      => in_array($status,['approved','paid']) ? now()->format('Y-m-d H:i:s') : null,
            'rejection_reason' => $status === 'rejected' ? '領収書の添付が不足しています。再申請してください。' : null,
            'notes'            => null,
        ];
    }
}
