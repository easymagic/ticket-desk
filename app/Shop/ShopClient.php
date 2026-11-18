<?php

namespace App\Shop;

use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * The agent's view of the shop. Tools call this, never the database directly.
 * The default "local" driver uses the in-process catalogue; the "http" driver
 * calls the same /api/shop endpoints over HTTP.
 */
class ShopClient
{
    public function __construct(private ShopCatalog $catalog) {}

    public function getTicket(int $id): array
    {
        return $this->request('GET', "/api/shop/tickets/{$id}", fn () => $this->catalog->ticket($id));
    }

    public function getCustomer(int $id): array
    {
        return $this->request('GET', "/api/shop/customers/{$id}", fn () => $this->catalog->customer($id));
    }

    public function getOrder(int $id): array
    {
        return $this->request('GET', "/api/shop/orders/{$id}", fn () => $this->catalog->order($id));
    }

    public function addNote(int $ticketId, string $body): array
    {
        return $this->request('POST', "/api/shop/tickets/{$ticketId}/notes", fn () => $this->catalog->addNote($ticketId, $body), [
            'body' => $body,
        ]);
    }

    public function setPriority(int $ticketId, string $priority): array
    {
        return $this->request('PATCH', "/api/shop/tickets/{$ticketId}", fn () => $this->catalog->setPriority($ticketId, $priority), [
            'priority' => $priority,
        ]);
    }

    public function draftReply(int $ticketId, string $body): array
    {
        return $this->request('POST', "/api/shop/tickets/{$ticketId}/draft-reply", fn () => $this->catalog->draftReply($ticketId, $body), [
            'body' => $body,
        ]);
    }

    public function requestRefund(int $orderId, int $amountCents, string $reason, ?int $ticketId = null, ?int $runId = null): array
    {
        return $this->request('POST', "/api/shop/orders/{$orderId}/refunds", fn () => $this->catalog->requestRefund(
            $orderId, $amountCents, $reason, $ticketId, $runId
        ), [
            'amount_cents' => $amountCents,
            'reason' => $reason,
            'ticket_id' => $ticketId,
            'triage_run_id' => $runId,
        ]);
    }

    public function shipping(string $trackingNumber): array
    {
        return $this->request('GET', '/api/shop/shipping/'.urlencode($trackingNumber), fn () => $this->catalog->shipping($trackingNumber));
    }

    /**
     * @param  callable(): array  $local
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, callable $local, array $payload = []): array
    {
        if (config('ticket-desk.shop_driver') !== 'http') {
            return $local();
        }

        $request = Http::withToken((string) config('ticket-desk.api_token'))
            ->acceptJson()
            ->timeout(10)
            ->baseUrl(rtrim((string) config('ticket-desk.shop_url'), '/'));

        $response = match (strtoupper($method)) {
            'GET' => $request->get($path),
            'PATCH' => $request->patch($path, $payload),
            default => $request->post($path, $payload),
        };

        try {
            $response->throw();
        } catch (RequestException $e) {
            throw $e;
        }

        return $response->json();
    }
}
