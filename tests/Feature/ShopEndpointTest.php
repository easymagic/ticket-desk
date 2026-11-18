<?php

namespace Tests\Feature;

use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_endpoints_are_available_over_http(): void
    {
        $ticket = Ticket::factory()->create();

        $this->getJson('/api/shop/tickets/'.$ticket->id, $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('id', $ticket->id);

        $this->getJson('/api/shop/orders/'.$ticket->order_id, $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('id', $ticket->order_id);

        $this->postJson('/api/shop/tickets/'.$ticket->id.'/notes', [
            'body' => 'Called the customer.',
        ], $this->authHeaders())->assertCreated();

        $this->assertDatabaseHas('ticket_notes', [
            'ticket_id' => $ticket->id,
            'body' => 'Called the customer.',
        ]);
    }
}
