<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->string('status');
            $table->unsignedInteger('total_cents');
            $table->string('currency', 3)->default('GBP');
            $table->string('tracking_number')->nullable();
            $table->json('items');
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('subject');
            $table->text('body');
            $table->string('priority')->default('untriaged');
            $table->string('status')->default('open');
            $table->text('draft_reply')->nullable();
            $table->timestamps();
        });

        Schema::create('ticket_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->text('body');
            $table->string('author')->default('agent');
            $table->timestamps();
        });

        Schema::create('help_articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('body');
            $table->json('embedding')->nullable();
            $table->timestamps();
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
            // Keep a parallel pgvector column for production similarity search.
            DB::statement('ALTER TABLE help_articles ADD COLUMN embedding_vector vector(16) NULL');
        }

        Schema::create('triage_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('queued');
            $table->string('idempotency_key')->unique();
            $table->string('conversation_id')->nullable();
            $table->json('result')->nullable();
            $table->json('trace')->nullable();
            $table->json('pending_approvals')->nullable();
            $table->unsignedInteger('steps')->default(0);
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('triage_run_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('amount_cents');
            $table->string('reason');
            $table->string('status')->default('requested');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
        Schema::dropIfExists('triage_runs');
        Schema::dropIfExists('help_articles');
        Schema::dropIfExists('ticket_notes');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('customers');
    }
};
