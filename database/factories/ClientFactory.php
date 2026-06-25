<?php
namespace Database\Factories;
use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition(): array
    {
        $companies = ['株式会社','有限会社','合同会社'];
        $types = ['customer','vendor','both'];
        $industries = ['テクノロジー','コンサルティング','製造','商社','小売','広告','物流','建設','医療','教育'];
        $name = fake('ja_JP')->randomElement($companies) . fake('ja_JP')->randomElement($industries) . fake('ja_JP')->lastName();

        return [
            'code'           => 'C' . str_pad($this->faker->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'name'           => $name,
            'name_kana'      => fake('ja_JP')->kanaName(),
            'type'           => fake()->randomElement($types),
            'postal_code'    => fake('ja_JP')->postcode(),
            'address'        => fake('ja_JP')->prefecture() . fake('ja_JP')->city() . fake('ja_JP')->streetAddress(),
            'phone'          => fake('ja_JP')->phoneNumber(),
            'email'          => fake()->companyEmail(),
            'contact_person' => fake('ja_JP')->name(),
            'payment_terms'  => fake()->randomElement([30, 45, 60, 90]),
            'is_active'      => true,
            'notes'          => null,
        ];
    }
}
