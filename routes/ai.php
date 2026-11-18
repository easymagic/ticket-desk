<?php

use App\Mcp\Servers\ShippingServer;
use App\Mcp\Servers\TicketDeskServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/ticket-desk', TicketDeskServer::class)
    ->middleware('ticket-desk.token');

Mcp::web('/mcp/shipping', ShippingServer::class)
    ->middleware('ticket-desk.token');

Mcp::local('ticket-desk', TicketDeskServer::class);
Mcp::local('shipping', ShippingServer::class);
