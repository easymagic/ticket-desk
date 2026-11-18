<?php

namespace Tests\Feature;

use App\Ai\Tools\GetOrder;
use App\Ai\Tools\GetTicket;
use App\Ai\Tools\SearchHelpDocs;
use App\Ai\Tools\SetPriority;
use App\Models\Ticket;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class ToolTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_ticket_and_get_order_call_the_shop(): void
    {
        $ticket = Ticket::factory()->create(['subject' => 'Where is my order?']);

        $ticketJson = (string) app(GetTicket::class)->handle(new Request(['ticket_id' => $ticket->id]));
        $ticketPayload = json_decode($ticketJson, true);
        $this->assertSame($ticket->id, $ticketPayload['id']);
        $this->assertSame('Where is my order?', $ticketPayload['subject']);

        $orderJson = (string) app(GetOrder::class)->handle(new Request(['order_id' => $ticket->order_id]));
        $orderPayload = json_decode($orderJson, true);
        $this->assertSame($ticket->order_id, $orderPayload['id']);
    }

    public function test_get_order_rejects_unknown_ids(): void
    {
        $json = (string) app(GetOrder::class)->handle(new Request(['order_id' => 9999]));
        $this->assertSame('Order not found.', json_decode($json, true)['error']);
    }

    public function test_set_priority_updates_the_ticket(): void
    {
        $ticket = Ticket::factory()->create();
        $json = (string) app(SetPriority::class)->handle(new Request([
            'ticket_id' => $ticket->id,
            'priority' => 'urgent',
        ]));

        $this->assertSame('urgent', json_decode($json, true)['priority']);
        $this->assertSame('urgent', $ticket->fresh()->priority);
    }

    public function test_search_help_docs_returns_a_returns_article(): void
    {
        $this->seedHelpArticles();

        $json = (string) app(SearchHelpDocs::class)->handle(new Request([
            'query' => 'How do I get a refund after delivery?',
        ]));
        $payload = json_decode($json, true);

        $this->assertNotEmpty($payload['hits']);
        $this->assertContains('returns', array_column($payload['hits'], 'slug'));
        $this->assertStringContainsString('untrusted', $payload['notice']);
    }
}
