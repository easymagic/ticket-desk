<?php

namespace App\Ai\Tools;

use App\Shop\ShopClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetCustomer implements Tool
{
    use EncodesJson;

    public function __construct(private ShopClient $shop) {}

    public function description(): Stringable|string
    {
        return 'Look up a customer by id, including their name, email, and recent orders.';
    }

    public function handle(Request $request): Stringable|string
    {
        $validated = $request->validate([
            'customer_id' => 'required|integer|min:1',
        ]);

        try {
            return $this->json($this->shop->getCustomer((int) $validated['customer_id']));
        } catch (ModelNotFoundException) {
            return $this->json(['error' => 'Customer not found.']);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_id' => $schema->integer()->min(1)->required(),
        ];
    }
}
