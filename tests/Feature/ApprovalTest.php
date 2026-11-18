<?php

namespace Tests\Feature;

use App\Ai\Agents\TriageAgent;
use App\Ai\Tools\RequestRefund;
use App\Models\RefundRequest;
use App\Models\Ticket;
use App\Models\TriageRun;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class ApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_refund_does_not_run_just_because_it_was_constructed(): void
    {
        $ticket = Ticket::factory()->create();
        $this->assertSame(0, RefundRequest::count());

        app(RequestRefund::class); // constructing the tool must not write a refund
        $this->assertSame(0, RefundRequest::count());

        $tool = app(RequestRefund::class);
        $tool->handle(new Request([
            'order_id' => $ticket->order_id,
            'amount_cents' => 4599,
            'reason' => 'Customer asked for a refund on a delivered order.',
            'ticket_id' => $ticket->id,
        ]));

        $this->assertSame(1, RefundRequest::count());
    }

    public function test_a_pending_refund_pauses_the_run_until_an_operator_decides(): void
    {
        $ticket = Ticket::factory()->create();

        TriageAgent::fake([
            AgentResponse::fakeWithPendingApprovals([
                new PendingApproval(
                    id: 'call_refund_1',
                    tool: 'RequestRefund',
                    arguments: [
                        'order_id' => $ticket->order_id,
                        'amount_cents' => 4599,
                        'reason' => 'Wrong size, refund requested',
                        'ticket_id' => $ticket->id,
                    ],
                    reason: 'Needs an operator.',
                ),
            ]),
            [
                'priority' => 'normal',
                'summary' => 'Refund approved and requested.',
                'draft_reply' => 'I have requested your refund.',
                'citations' => ['returns'],
                'confidence' => 0.8,
                'needs_refund' => true,
            ],
        ]);

        $triage = $this->postJson("/api/tickets/{$ticket->id}/triage", [], $this->authHeaders());
        $triage->assertStatus(202)->assertJsonPath('status', 'waiting-for-approval');
        $this->assertSame(0, RefundRequest::count());

        $runId = $triage->json('id');
        $this->postJson("/api/triage-runs/{$runId}/approvals", [
            'approve' => false,
            'approval_id' => 'call_refund_1',
            'reason' => 'Photos are missing.',
        ], $this->authHeaders())->assertOk()->assertJsonPath('status', 'succeeded');

        $this->assertSame(0, RefundRequest::count());
        $this->assertSame(TriageRun::STATUS_SUCCEEDED, TriageRun::find($runId)->status);
    }
}
