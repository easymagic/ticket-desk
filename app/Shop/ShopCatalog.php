<?php

namespace App\Shop;

use App\Models\Customer;
use App\Models\Order;
use App\Models\RefundRequest;
use App\Models\Ticket;
use App\Models\TicketNote;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ShopCatalog
{
    public function ticket(int $id): array
    {
        $ticket = Ticket::with(['customer', 'order', 'notes'])->findOrFail($id);

        return $this->ticketPayload($ticket);
    }

    public function customer(int $id): array
    {
        $customer = Customer::with('orders')->findOrFail($id);

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'orders' => $customer->orders->map(fn (Order $order) => [
                'id' => $order->id,
                'number' => $order->number,
                'status' => $order->status,
                'total_cents' => $order->total_cents,
            ])->all(),
        ];
    }

    public function order(int $id): array
    {
        $order = Order::with('customer')->findOrFail($id);

        return $this->orderPayload($order);
    }

    public function addNote(int $ticketId, string $body, string $author = 'agent'): array
    {
        $ticket = Ticket::findOrFail($ticketId);
        $note = $ticket->notes()->create([
            'body' => $body,
            'author' => $author,
        ]);

        return ['id' => $note->id, 'ticket_id' => $ticket->id, 'body' => $note->body];
    }

    public function setPriority(int $ticketId, string $priority): array
    {
        $allowed = ['low', 'normal', 'high', 'urgent'];
        if (! in_array($priority, $allowed, true)) {
            throw new \InvalidArgumentException('Priority must be one of: '.implode(', ', $allowed));
        }

        $ticket = Ticket::findOrFail($ticketId);
        $ticket->update(['priority' => $priority]);

        return ['id' => $ticket->id, 'priority' => $ticket->priority];
    }

    public function draftReply(int $ticketId, string $body): array
    {
        $ticket = Ticket::findOrFail($ticketId);
        $ticket->update(['draft_reply' => $body]);

        return ['id' => $ticket->id, 'draft_reply' => $ticket->draft_reply];
    }

    public function requestRefund(int $orderId, int $amountCents, string $reason, ?int $ticketId = null, ?int $runId = null): array
    {
        $order = Order::findOrFail($orderId);
        $refund = RefundRequest::create([
            'order_id' => $order->id,
            'ticket_id' => $ticketId,
            'triage_run_id' => $runId,
            'amount_cents' => $amountCents,
            'reason' => $reason,
            'status' => 'requested',
        ]);

        return [
            'id' => $refund->id,
            'order_id' => $order->id,
            'amount_cents' => $refund->amount_cents,
            'status' => $refund->status,
        ];
    }

    public function shipping(string $trackingNumber): array
    {
        $order = Order::where('tracking_number', $trackingNumber)->firstOrFail();

        $eta = match ($order->status) {
            'shipped' => now()->addDays(2)->toDateString(),
            'delivered' => null,
            default => now()->addDays(5)->toDateString(),
        };

        return [
            'tracking_number' => $trackingNumber,
            'status' => $order->status,
            'eta' => $eta,
            'order_number' => $order->number,
        ];
    }

    public function ticketPayload(Ticket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'body' => $ticket->body,
            'priority' => $ticket->priority,
            'status' => $ticket->status,
            'draft_reply' => $ticket->draft_reply,
            'customer_id' => $ticket->customer_id,
            'order_id' => $ticket->order_id,
            'notes' => $ticket->notes->map(fn (TicketNote $note) => [
                'id' => $note->id,
                'body' => $note->body,
                'author' => $note->author,
            ])->all(),
        ];
    }

    public function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'number' => $order->number,
            'status' => $order->status,
            'total_cents' => $order->total_cents,
            'currency' => $order->currency,
            'tracking_number' => $order->tracking_number,
            'items' => $order->items,
            'customer_id' => $order->customer_id,
        ];
    }

    public function notFoundMessage(ModelNotFoundException $e): string
    {
        return 'Not found: '.$e->getMessage();
    }
}
