<?php

namespace App\Http\Controllers;

use App\Jobs\TriageTicketJob;
use App\Models\Ticket;
use App\Models\TriageRun;
use App\Shop\ShopCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    public function store(Request $request, ShopCatalog $shop): JsonResponse
    {
        $data = $request->validate([
            'customer_id' => 'required|integer|exists:customers,id',
            'order_id' => 'nullable|integer|exists:orders,id',
            'subject' => 'required|string|max:200',
            'body' => 'required|string|max:8000',
        ]);

        $ticket = Ticket::create($data + ['priority' => 'untriaged', 'status' => 'open']);

        return response()->json($shop->ticket($ticket->id), 201);
    }

    public function show(Ticket $ticket, ShopCatalog $shop): JsonResponse
    {
        return response()->json($shop->ticket($ticket->id));
    }

    public function triage(Request $request, Ticket $ticket): JsonResponse
    {
        $key = $request->header('Idempotency-Key') ?: $request->input('idempotency_key') ?: (string) Str::uuid();

        $existing = TriageRun::query()->where('idempotency_key', $key)->first();
        if ($existing) {
            return response()->json($this->runPayload($existing), $existing->wasRecentlyCreated ? 202 : 202);
        }

        $run = TriageRun::create([
            'ticket_id' => $ticket->id,
            'status' => TriageRun::STATUS_QUEUED,
            'idempotency_key' => $key,
        ]);

        TriageTicketJob::dispatch($run->id);

        return response()->json($this->runPayload($run->fresh()), 202);
    }

    private function runPayload(TriageRun $run): array
    {
        return [
            'id' => $run->id,
            'ticket_id' => $run->ticket_id,
            'status' => $run->status,
            'idempotency_key' => $run->idempotency_key,
        ];
    }
}
