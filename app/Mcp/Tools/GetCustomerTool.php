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

#[Description('Look up a customer by id.')]
#[IsReadOnly]
#[IsIdempotent]
class GetCustomerTool extends Tool
{
    public function handle(Request $request): Response
    {
        $id = (int) $request->validate(['customer_id' => 'required|integer|min:1'])['customer_id'];
        try {
            return Response::text(json_encode(app(ShopCatalog::class)->customer($id), JSON_UNESCAPED_SLASHES));
        } catch (ModelNotFoundException) {
            return Response::text(json_encode(['error' => 'Customer not found.']));
        }
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'customer_id' => $schema->integer()->required(),
        ];
    }
}
