<?php

namespace App\Ai\Tools;

use App\Shop\ShopClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class SetPriority implements Tool
{
    use EncodesJson;

    public function __construct(private ShopClient $shop) {}

    public function description(): Stringable|string
    {
        return 'Set a ticket priority to one of: low, normal, high, urgent.';
    }

    public function handle(Request $request): Stringable|string
    {
        $validated = $request->validate([
            'ticket_id' => 'required|integer|min:1',
            'priority' => 'required|string|in:low,normal,high,urgent',
        ]);

        try {
            return $this->json($this->shop->setPriority((int) $validated['ticket_id'], $validated['priority']));
        } catch (ModelNotFoundException) {
            return $this->json(['error' => 'Ticket not found.']);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()]);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->min(1)->required(),
            'priority' => $schema->string()->enum(['low', 'normal', 'high', 'urgent'])->required(),
        ];
    }
}
