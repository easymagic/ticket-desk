<?php

namespace Tests\Feature;

use App\Mcp\Servers\ShippingServer;
use App\Mcp\Servers\TicketDeskServer;
use App\Mcp\Tools\GetTicketTool;
use App\Mcp\Tools\GetShippingStatusTool;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class McpTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_ticket_desk_mcp_server_returns_a_ticket(): void
    {
        $ticket = Ticket::factory()->create(['subject' => 'MCP lookup']);

        $response = TicketDeskServer::tool(GetTicketTool::class, [
            'ticket_id' => $ticket->id,
        ]);

        $response->assertOk()->assertSee('MCP lookup');
    }

    public function test_the_shipping_mcp_server_returns_tracking(): void
    {
        $ticket = Ticket::factory()->create();

        $response = ShippingServer::tool(GetShippingStatusTool::class, [
            'tracking_number' => $ticket->order->tracking_number,
        ]);

        $response->assertOk()->assertSee($ticket->order->number);
    }
}
