<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'number' => 'NW-'.fake()->unique()->numerify('#####'),
            'status' => 'delivered',
            'total_cents' => 4599,
            'currency' => 'GBP',
            'tracking_number' => 'TRK'.fake()->unique()->numerify('########'),
            'items' => [
                ['sku' => 'BOOT-42', 'name' => 'Walking boots', 'qty' => 1, 'price_cents' => 4599],
            ],
        ];
    }
}
