<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'order_id' => Order::factory(),
            'subject' => 'Wrong size',
            'body' => 'The boots are a size too small. I would like to exchange them or have a refund.',
            'priority' => 'untriaged',
            'status' => 'open',
        ];
    }
}
