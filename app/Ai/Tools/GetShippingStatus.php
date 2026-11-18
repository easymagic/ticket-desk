<?php

namespace App\Ai\Tools;

use App\Shop\ShopClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetShippingStatus implements Tool
{
    use EncodesJson;

    public function __construct(private ShopClient $shop) {}

    public function description(): Stringable|string
    {
        return 'Look up a shipment by tracking number and return its status and estimated arrival.';
    }

    public function handle(Request $request): Stringable|string
    {
        $validated = $request->validate([
            'tracking_number' => 'required|string|min:4|max:64',
        ]);

        try {
            return $this->json($this->shop->shipping($validated['tracking_number']));
        } catch (ModelNotFoundException) {
            return $this->json(['error' => 'Tracking number not found.']);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'tracking_number' => $schema->string()->required(),
        ];
    }
}
