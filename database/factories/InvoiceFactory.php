<?php
namespace Database\Factories;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(100000, 3000000);
        $tax      = (int)round($subtotal * 0.1);
        $total    = $subtotal + $tax;
        $status   = fake()->randomElement(['draft','sent','sent','paid','overdue']);
        $invoiceDate = fake()->dateTimeBetween('-4 months','now');
        $dueDate     = (clone $invoiceDate)->modify('+30 days');

        return [
            'invoice_number' => 'INV-' . $invoiceDate->format('Ym') . '-' . str_pad(fake()->unique()->numberBetween(1,999),3,'0',STR_PAD_LEFT),
            'client_id'      => Client::inRandomOrder()->value('id') ?? Client::factory(),
            'user_id'        => User::inRandomOrder()->value('id') ?? User::factory(),
            'invoice_date'   => $invoiceDate->format('Y-m-d'),
            'due_date'       => $dueDate->format('Y-m-d'),
            'subtotal'       => $subtotal,
            'tax_amount'     => $tax,
            'total_amount'   => $total,
            'paid_amount'    => $status === 'paid' ? $total : 0,
            'status'         => $status,
            'sent_at'        => in_array($status,['sent','paid','overdue']) ? $invoiceDate->format('Y-m-d H:i:s') : null,
            'notes'          => null,
        ];
    }
}
