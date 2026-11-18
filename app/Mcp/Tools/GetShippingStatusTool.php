<?php

namespace App\Mcp\Tools;

use App\Shop\ShopCatalog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tool;

#[Description('Look up a shipment by tracking number.')]
#[IsReadOnly]
#[IsIdempotent]
class GetShippingStatusTool extends Tool
{
    public function handle(Request $request): Response
    {
        $tracking = $request->validate(['tracking_number' => 'required|string'])['tracking_number'];
        try {
            return Response::text(json_encode(app(ShopCatalog::class)->shipping($tracking), JSON_UNESCAPED_SLASHES));
        } catch (ModelNotFoundException) {
            return Response::text(json_encode(['error' => 'Tracking number not found.']));
        }
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'tracking_number' => $schema->string()->required(),
        ];
    }
}
