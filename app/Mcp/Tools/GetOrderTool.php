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

#[Description('Look up an order by id.')]
#[IsReadOnly]
#[IsIdempotent]
class GetOrderTool extends Tool
{
    public function handle(Request $request): Response
    {
        $id = (int) $request->validate(['order_id' => 'required|integer|min:1'])['order_id'];
        try {
            return Response::text(json_encode(app(ShopCatalog::class)->order($id), JSON_UNESCAPED_SLASHES));
        } catch (ModelNotFoundException) {
            return Response::text(json_encode(['error' => 'Order not found.']));
        }
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->integer()->required(),
        ];
    }
}
