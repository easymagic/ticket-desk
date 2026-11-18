<?php

namespace App\Ai\Tools;

use App\Shop\ShopClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetOrder implements Tool
{
    use EncodesJson;

    public function __construct(private ShopClient $shop) {}

    public function description(): Stringable|string
    {
        return 'Look up an order by id, including status, totals, line items, and tracking number.';
    }

    public function handle(Request $request): Stringable|string
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|min:1',
        ]);

        try {
            return $this->json($this->shop->getOrder((int) $validated['order_id']));
        } catch (ModelNotFoundException) {
            return $this->json(['error' => 'Order not found.']);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->integer()->min(1)->required(),
        ];
    }
}
