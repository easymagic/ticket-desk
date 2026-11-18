<?php

namespace App\Ai\Tools;

use App\Shop\ShopClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetTicket implements Tool
{
    use EncodesJson;

    public function __construct(private ShopClient $shop) {}

    public function description(): Stringable|string
    {
        return 'Look up a support ticket by id, including its subject, body, priority, linked customer and order ids, and internal notes.';
    }

    public function handle(Request $request): Stringable|string
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer|min:1',
        ]);

        try {
            return $this->json($this->shop->getTicket((int) $validated['ticket_id']));
        } catch (ModelNotFoundException) {
            return $this->json(['error' => 'Ticket not found.']);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->min(1)->required(),
        ];
    }
}
