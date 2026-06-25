<?php
namespace Database\Factories;
use App\Models\Client;
use App\Models\User;
use App\Models\AccountItem;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        $status = fake()->randomElement(['pending','approved','paid']);
        $dueDate = fake()->dateTimeBetween('-2 months','now');
        return [
            'payment_number'  => 'PAY-' . fake()->unique()->numerify('######'),
            'client_id'       => Client::inRandomOrder()->value('id') ?? Client::factory(),
            'user_id'         => User::inRandomOrder()->value('id') ?? User::factory(),
            'due_date'        => $dueDate->format('Y-m-d'),
            'payment_date'    => $status === 'paid' ? $dueDate->format('Y-m-d') : null,
            'amount'          => fake()->numberBetween(10000, 500000),
            'method'          => fake()->randomElement(['bank_transfer','cash']),
            'description'     => fake()->randomElement(['仕入代金','外注費','賃料','光熱費','通信費']),
            'status'          => $status,
            'account_item_id' => AccountItem::where('category','expense')->inRandomOrder()->value('id'),
            'notes'           => null,
        ];
    }
}
