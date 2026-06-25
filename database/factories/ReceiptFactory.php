<?php
namespace Database\Factories;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReceiptFactory extends Factory
{
    public function definition(): array
    {
        return [
            'receipt_number' => 'RCP-' . fake()->unique()->numerify('######'),
            'client_id'      => Client::inRandomOrder()->value('id') ?? Client::factory(),
            'invoice_id'     => null,
            'user_id'        => User::inRandomOrder()->value('id') ?? User::factory(),
            'receipt_date'   => fake()->dateTimeBetween('-3 months','now')->format('Y-m-d'),
            'amount'         => fake()->numberBetween(50000, 2000000),
            'method'         => fake()->randomElement(['bank_transfer','bank_transfer','bank_transfer','cash','credit_card']),
            'bank_name'      => fake()->randomElement(['三菱UFJ銀行','三井住友銀行','みずほ銀行','りそな銀行','楽天銀行']),
            'account_number' => fake()->numerify('#######'),
            'notes'          => null,
        ];
    }
}
