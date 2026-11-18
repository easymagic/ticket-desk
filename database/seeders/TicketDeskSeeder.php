<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\HelpArticle;
use App\Models\Order;
use App\Models\Ticket;
use App\Support\Embedder;
use App\Support\OperatorAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TicketDeskSeeder extends Seeder
{
    public function run(): void
    {
        OperatorAccount::user();

        $embedder = app(Embedder::class);

        $articles = [
            'returns' => 'Returns and refunds',
            'shipping' => 'Shipping and tracking',
            'exchanges' => 'Size exchanges',
            'damaged' => 'Damaged items',
        ];

        foreach ($articles as $slug => $title) {
            $body = file_get_contents(resource_path("help/{$slug}.md"));
            HelpArticle::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => $title,
                    'body' => $body,
                    'embedding' => $embedder->embed($title."\n".$body),
                ]
            );
        }

        $maya = Customer::query()->firstOrCreate(
            ['email' => 'maya@example.com'],
            ['name' => 'Maya Chen']
        );

        $order = Order::query()->firstOrCreate(
            ['number' => 'NW-1842'],
            [
                'customer_id' => $maya->id,
                'status' => 'delivered',
                'total_cents' => 4599,
                'currency' => 'GBP',
                'tracking_number' => 'TRK18420001',
                'items' => [
                    ['sku' => 'BOOT-8', 'name' => 'Walking boots, size 8', 'qty' => 1, 'price_cents' => 4599],
                ],
            ]
        );

        Ticket::query()->firstOrCreate(
            ['subject' => 'Boots are the wrong size', 'customer_id' => $maya->id],
            [
                'order_id' => $order->id,
                'body' => 'Hi, I ordered walking boots (order NW-1842) and they are a size too small. Can I exchange them for a size 9, or get a refund?',
                'priority' => 'untriaged',
                'status' => 'open',
            ]
        );
    }
}
