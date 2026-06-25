<?php
namespace Database\Factories;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SaleFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(50000, 2000000);
        $taxRate  = '10';
        $tax      = (int)round($subtotal * 0.1);

        return [
            'sale_number'  => 'S' . now()->format('Ymd') . str_pad(fake()->unique()->numberBetween(1,999),3,'0',STR_PAD_LEFT),
            'client_id'    => Client::inRandomOrder()->value('id') ?? Client::factory(),
            'user_id'      => User::inRandomOrder()->value('id') ?? User::factory(),
            'sale_date'    => fake()->dateTimeBetween('-6 months', 'now')->format('Y-m-d'),
            'description'  => fake()->randomElement(['システム開発費','コンサルティング費','保守サポート費','制作費','研修費','ライセンス費']),
            'subtotal'     => $subtotal,
            'tax_amount'   => $tax,
            'total_amount' => $subtotal + $tax,
            'tax_rate'     => $taxRate,
            'status'       => fake()->randomElement(['pending','invoiced','paid']),
            'notes'        => null,
        ];
    }
}
