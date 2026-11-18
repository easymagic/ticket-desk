<?php

namespace App\Jobs;

use App\Ai\Agents\RefundReviewer;
use App\Ai\Agents\TriageAgent;
use App\Models\Ticket;
use App\Models\TriageRun;
use App\Support\OperatorAccount;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Responses\AgentResponse;
use Throwable;

class TriageTicketJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;


    public function __construct(public int $runId)
    {
        $this->timeout = (int) config('ticket-desk.job_timeout', 120);
        $this->afterCommit = false;
    }

    public function handle(): void
    {
        $run = TriageRun::query()->with('ticket')->findOrFail($this->runId);
        $run->update(['status' => TriageRun::STATUS_RUNNING]);

        $operator = OperatorAccount::user();
        $ticket = $run->ticket;

        try {
            $agent = (new TriageAgent($ticket, $run))->forUser($operator);
            if ($run->conversation_id) {
                $agent = $agent->continue($run->conversation_id, as: $operator);
            }

            $response = $agent->prompt($this->promptFor($ticket));
            $this->record($run, $response);

            if ($run->fresh()->status === TriageRun::STATUS_SUCCEEDED) {
                $this->maybeReviewRefund($run->fresh(), $ticket, $operator);
            }
        } catch (Throwable $e) {
            $run->update([
                'status' => TriageRun::STATUS_FAILED,
                'error' => $e->getMessage(),
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    private function promptFor(Ticket $ticket): string
    {
        return "Triage ticket #{$ticket->id}: {$ticket->subject}\n\n{$ticket->body}";
    }

    public function record(TriageRun $run, AgentResponse $response): void
    {
        $trace = [
            'tool_calls' => $response->toolCalls->map(fn ($call) => [
                'name' => $call->name,
                'arguments' => $call->arguments,
            ])->all(),
            'steps' => $response->steps->map(fn ($step) => [
                'text' => $step->text,
                'tool_calls' => collect($step->toolCalls)->map(fn ($call) => [
                    'name' => $call->name,
                    'arguments' => $call->arguments,
                ])->all(),
            ])->all(),
        ];

        $usage = $response->usage;
        $payload = [
            'conversation_id' => $response->conversationId ?? $run->conversation_id,
            'trace' => $trace,
            'steps' => $response->steps->count(),
            'input_tokens' => $usage->inputTokens,
            'output_tokens' => $usage->outputTokens,
        ];

        if ($response->hasPendingApprovals()) {
            $run->update([
                ...$payload,
                'status' => TriageRun::STATUS_WAITING_FOR_APPROVAL,
                'pending_approvals' => collect($response->pendingApprovals)->map(fn (PendingApproval $approval) => [
                    'id' => $approval->id,
                    'tool' => $approval->tool,
                    'arguments' => $approval->arguments,
                    'reason' => $approval->reason,
                ])->all(),
            ]);

            return;
        }

        $result = method_exists($response, 'toArray') ? $response->toArray() : ['text' => (string) $response];

        $run->update([
            ...$payload,
            'status' => TriageRun::STATUS_SUCCEEDED,
            'result' => $result,
            'pending_approvals' => null,
            'finished_at' => now(),
        ]);
    }

    private function maybeReviewRefund(TriageRun $run, Ticket $ticket, $operator): void
    {
        $result = $run->result ?? [];
        if (! ($result['needs_refund'] ?? false)) {
            return;
        }

        $order = $ticket->order;
        if (! $order || $order->total_cents < (int) (config('ticket-desk.refund_review_threshold') * 100)) {
            return;
        }

        $review = (new RefundReviewer($ticket, $run))
            ->forUser($operator)
            ->prompt("Review the refund for ticket #{$ticket->id} and order #{$order->id}.");

        $current = $run->result ?? [];
        $current['refund_review'] = method_exists($review, 'toArray') ? $review->toArray() : ['text' => (string) $review];
        $run->update(['result' => $current]);
    }
}
