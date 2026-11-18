<?php

namespace App\Http\Controllers;

use App\Shop\ShopCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function __construct(private ShopCatalog $shop) {}

    public function ticket(int $ticket): JsonResponse
    {
        return response()->json($this->shop->ticket($ticket));
    }

    public function customer(int $customer): JsonResponse
    {
        return response()->json($this->shop->customer($customer));
    }

    public function order(int $order): JsonResponse
    {
        return response()->json($this->shop->order($order));
    }

    public function addNote(Request $request, int $ticket): JsonResponse
    {
        $data = $request->validate(['body' => 'required|string|min:3|max:2000']);

        return response()->json($this->shop->addNote($ticket, $data['body']), 201);
    }

    public function updateTicket(Request $request, int $ticket): JsonResponse
    {
        $data = $request->validate(['priority' => 'required|string|in:low,normal,high,urgent']);

        return response()->json($this->shop->setPriority($ticket, $data['priority']));
    }

    public function draftReply(Request $request, int $ticket): JsonResponse
    {
        $data = $request->validate(['body' => 'required|string|min:10|max:5000']);

        return response()->json($this->shop->draftReply($ticket, $data['body']));
    }

    public function refund(Request $request, int $order): JsonResponse
    {
        $data = $request->validate([
            'amount_cents' => 'required|integer|min:1',
            'reason' => 'required|string|min:5|max:1000',
            'ticket_id' => 'nullable|integer',
            'triage_run_id' => 'nullable|integer',
        ]);

        return response()->json($this->shop->requestRefund(
            $order,
            $data['amount_cents'],
            $data['reason'],
            $data['ticket_id'] ?? null,
            $data['triage_run_id'] ?? null,
        ), 201);
    }

    public function shipping(string $tracking): JsonResponse
    {
        return response()->json($this->shop->shipping($tracking));
    }
}
