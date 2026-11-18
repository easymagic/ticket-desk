<?php

namespace App\Http\Controllers;

use App\Ai\Agents\TriageAgent;
use App\Models\TriageRun;
use App\Support\OperatorAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Ai\Approvals\Decision;
use Laravel\Ai\Approvals\Decisions;

class TriageRunController extends Controller
{
    public function show(TriageRun $run): JsonResponse
    {
        return response()->json([
            'id' => $run->id,
            'ticket_id' => $run->ticket_id,
            'status' => $run->status,
            'result' => $run->result,
            'pending_approvals' => $run->pending_approvals,
            'steps' => $run->steps,
            'input_tokens' => $run->input_tokens,
            'output_tokens' => $run->output_tokens,
            'error' => $run->error,
            'finished_at' => $run->finished_at,
        ]);
    }

    public function approve(Request $request, TriageRun $run): JsonResponse
    {
        $request->validate([
            'approve' => 'required|boolean',
            'approval_id' => 'nullable|string',
            'reason' => 'nullable|string|max:500',
        ]);

        if ($run->status !== TriageRun::STATUS_WAITING_FOR_APPROVAL || ! $run->conversation_id) {
            return response()->json(['message' => 'This run is not waiting for approval.'], 422);
        }

        $approvals = $run->pending_approvals ?? [];
        $approvalId = $request->input('approval_id') ?? ($approvals[0]['id'] ?? null);
        if (! $approvalId) {
            return response()->json(['message' => 'No pending approval id.'], 422);
        }

        $decision = $request->boolean('approve')
            ? Decision::approve()
            : Decision::reject($request->input('reason') ?: 'Not approved.');

        $operator = OperatorAccount::user();
        $response = (new TriageAgent($run->ticket, $run))
            ->continue($run->conversation_id, as: $operator)
            ->prompt(Decisions::from([
                $approvalId => $decision,
            ])->rejectRemaining('Not approved.'));

        (new \App\Jobs\TriageTicketJob($run->id))->record($run->fresh(), $response);

        return response()->json([
            'id' => $run->id,
            'status' => $run->fresh()->status,
            'result' => $run->fresh()->result,
            'pending_approvals' => $run->fresh()->pending_approvals,
        ]);
    }
}
