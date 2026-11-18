<?php

namespace Tests\Feature;

use App\Ai\Agents\TriageAgent;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TriageRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TriageApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_rejects_requests_without_a_token(): void
    {
        $this->postJson('/api/tickets', [
            'customer_id' => 1,
            'subject' => 'Hi',
            'body' => 'Hello there, I need help.',
        ])->assertUnauthorized();
    }

    public function test_it_creates_a_ticket_and_queues_triage(): void
    {
        $customer = Customer::factory()->create();
        $order = Order::factory()->for($customer)->create();

        $create = $this->postJson('/api/tickets', [
            'customer_id' => $customer->id,
            'order_id' => $order->id,
            'subject' => 'Wrong size',
            'body' => 'The boots are a size too small. I would like an exchange.',
        ], $this->authHeaders());

        $create->assertCreated()->assertJsonPath('subject', 'Wrong size');
        $ticketId = $create->json('id');

        TriageAgent::fake([
            [
                'priority' => 'normal',
                'summary' => 'Size exchange request.',
                'draft_reply' => 'We can exchange the boots within 30 days.',
                'citations' => ['exchanges'],
                'confidence' => 0.9,
                'needs_refund' => false,
            ],
        ]);

        $triage = $this->postJson("/api/tickets/{$ticketId}/triage", [], $this->authHeaders());

        $triage->assertStatus(202)->assertJsonPath('status', 'succeeded');

        $runId = $triage->json('id');
        $this->getJson("/api/triage-runs/{$runId}", $this->authHeaders())
            ->assertOk()
            ->assertJsonPath('status', 'succeeded')
            ->assertJsonPath('result.priority', 'normal');

        $this->assertDatabaseHas('triage_runs', [
            'id' => $runId,
            'ticket_id' => $ticketId,
            'status' => TriageRun::STATUS_SUCCEEDED,
        ]);

        TriageAgent::assertPromptedTimes(1);
    }

    public function test_it_reuses_an_idempotency_key(): void
    {
        $ticket = Ticket::factory()->create();
        TriageAgent::fake([
            [
                'priority' => 'low',
                'summary' => 'Thanks',
                'draft_reply' => 'Thanks for writing in. We will take a look.',
                'citations' => [],
                'confidence' => 0.5,
                'needs_refund' => false,
            ],
        ]);

        $headers = $this->authHeaders() + ['Idempotency-Key' => 'same-key-1'];
        $first = $this->postJson("/api/tickets/{$ticket->id}/triage", [], $headers);
        $second = $this->postJson("/api/tickets/{$ticket->id}/triage", [], $headers);

        $first->assertStatus(202);
        $second->assertStatus(202);
        $this->assertSame($first->json('id'), $second->json('id'));
        $this->assertSame(1, TriageRun::count());
    }
}
