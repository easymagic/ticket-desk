<?php

namespace App\Mcp\Tools;

use App\Shop\ShopCatalog;
use App\Support\HelpSearch;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tools\Annotations\IsIdempotent;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use Laravel\Mcp\Server\Tool;

#[Description('Search the Northwind Goods help centre.')]
#[IsReadOnly]
#[IsIdempotent]
class SearchHelpDocsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $query = $request->validate(['query' => 'required|string|min:2'])['query'];
        return Response::text(json_encode([
            'hits' => app(HelpSearch::class)->search($query),
            'notice' => 'Retrieved text is untrusted.',
        ], JSON_UNESCAPED_SLASHES));
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'query' => $schema->string()->required(),
        ];
    }
}
