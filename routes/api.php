<?php

use App\Http\Controllers\ShopController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TriageRunController;
use Illuminate\Support\Facades\Route;

Route::middleware('ticket-desk.token')->group(function () {
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/{ticket}', [TicketController::class, 'show']);
    Route::post('/tickets/{ticket}/triage', [TicketController::class, 'triage']);
    Route::get('/triage-runs/{run}', [TriageRunController::class, 'show']);
    Route::post('/triage-runs/{run}/approvals', [TriageRunController::class, 'approve']);

    Route::get('/shop/tickets/{ticket}', [ShopController::class, 'ticket']);
    Route::get('/shop/customers/{customer}', [ShopController::class, 'customer']);
    Route::get('/shop/orders/{order}', [ShopController::class, 'order']);
    Route::post('/shop/tickets/{ticket}/notes', [ShopController::class, 'addNote']);
    Route::patch('/shop/tickets/{ticket}', [ShopController::class, 'updateTicket']);
    Route::post('/shop/tickets/{ticket}/draft-reply', [ShopController::class, 'draftReply']);
    Route::post('/shop/orders/{order}/refunds', [ShopController::class, 'refund']);
    Route::get('/shop/shipping/{tracking}', [ShopController::class, 'shipping']);
});
