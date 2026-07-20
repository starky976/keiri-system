<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code'       => fake()->unique()->numerify('####'),
            'name'       => fake()->randomElement(['売上高','仕入高','現金','買掛金','未払費用','旅費交通費','通信費']),
            'category'   => fake()->randomElement(['asset','liability','equity','revenue','expense']),
            'is_active'  => true,
            'sort_order' => fake()->numberBetween(1, 999),
        ];
    }
}
