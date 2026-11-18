<?php

namespace App\Ai\Tools;

use App\Shop\ShopClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class DraftReply implements Tool
{
    use EncodesJson;

    public function __construct(private ShopClient $shop) {}

    public function description(): Stringable|string
    {
        return 'Save a draft reply on a ticket. The draft is not sent to the customer automatically.';
    }

    public function handle(Request $request): Stringable|string
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer|min:1',
            'body' => 'required|string|min:10|max:5000',
        ]);

        try {
            return $this->json($this->shop->draftReply((int) $validated['ticket_id'], $validated['body']));
        } catch (ModelNotFoundException) {
            return $this->json(['error' => 'Ticket not found.']);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->min(1)->required(),
            'body' => $schema->string()->required(),
        ];
    }
}
