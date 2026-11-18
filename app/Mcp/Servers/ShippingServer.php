<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\GetShippingStatusTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Northwind Shipping')]
#[Version('1.0.0')]
#[Instructions('Look up shipment status by tracking number.')]
class ShippingServer extends Server
{
    protected array $tools = [
        GetShippingStatusTool::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
