# ticket-desk

Companion Laravel project for *Agentic AI for Backend Engineers* by Nnamdi Alexander Akamu.

`ticket-desk` is a support-ticket triage agent for a fictional shop, **Northwind Goods**. An operator posts a ticket id to a REST endpoint; a queue worker runs a bounded agent; the agent looks up the customer and order, searches help docs, sets a priority, drafts a reply, and — only with a human approval — can request a refund. The same read tools are also exposed as an MCP server.

## Requirements

- PHP 8.3+ (8.4 recommended) with the `pdo_sqlite` or `pdo_pgsql`, `redis`, `mbstring`, `curl`, and `xml` extensions
- Composer
- Docker, if you want Postgres with pgvector and Redis (optional for the test suite)

## Quick start

```bash
cp .env.example .env
# set TICKET_DESK_API_TOKEN, and optionally OPENAI_COMPATIBLE_URL / OPENAI_COMPATIBLE_API_KEY
composer install
php artisan key:generate
php artisan migrate --seed
```

The test suite uses SQLite in memory and a fake model driver. It does **not** need an API key:

```bash
php artisan test
php artisan ticket-desk:eval
```

## Talking to a real model

Set these in `.env` and point them at any OpenAI-compatible API (OpenAI, Groq, OpenRouter, Ollama, a local gateway):

```
AI_PROVIDER=openai-compatible
OPENAI_COMPATIBLE_URL=http://127.0.0.1:11434/v1
OPENAI_COMPATIBLE_API_KEY=not-needed-for-ollama
OPENAI_COMPATIBLE_MODEL=llama3.2
TICKET_DESK_EMBEDDINGS_ENABLED=true
```

Then run `php artisan ticket-desk:embed-help` and start a worker:

```bash
php artisan queue:work
```

## Docker (Postgres + Redis)

```bash
docker compose up -d
```

Then in `.env`:

```
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ticket_desk
DB_USERNAME=ticket
DB_PASSWORD=secret
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
```

## HTTP API

All routes require `Authorization: Bearer $TICKET_DESK_API_TOKEN`.

| Method | Path | Purpose |
| --- | --- | --- |
| `POST` | `/api/tickets` | Create a ticket |
| `GET` | `/api/tickets/{id}` | Show a ticket |
| `POST` | `/api/tickets/{id}/triage` | Queue a triage run (`202`) |
| `GET` | `/api/triage-runs/{id}` | Inspect a run |
| `POST` | `/api/triage-runs/{id}/approvals` | Approve or reject a refund |
| `GET/POST/PATCH` | `/api/shop/*` | The shop endpoints the tools call |

`POST /api/tickets/{id}/triage` honours an `Idempotency-Key` header.

A run is `queued`, `running`, `succeeded`, `failed`, or `waiting-for-approval`.

## MCP

- HTTP: `POST /mcp/ticket-desk` and `POST /mcp/shipping` (same bearer token)
- Local: `php artisan mcp:start ticket-desk` or `php artisan mcp:start shipping`

## Chapter mapping

| Chapter | What to look at |
| --- | --- |
| 1. An agent is a loop | `app/Ai/Agents/TriageAgent.php`, `routes/api.php` |
| 2. The model is a dependency | `config/ai.php` (`openai-compatible` provider), `.env.example` |
| 3. Tools that call real endpoints | `app/Ai/Tools/*`, `app/Shop/ShopClient.php`, `app/Http/Controllers/ShopController.php` |
| 4. The loop, the plan, and the budget | `TriageAgent` attributes (`MaxSteps`), `app/Ai/Middleware/BudgetGuard.php` |
| 5. REST service and queue worker | `TicketController`, `app/Jobs/TriageTicketJob.php` |
| 6. RAG over your own docs | `app/Support/HelpSearch.php`, `app/Support/Embedder.php`, `resources/help/` |
| 7. MCP | `app/Mcp/Servers/*`, `routes/ai.php` |
| 8. Multi-agent, only when it pays | `app/Ai/Agents/RefundReviewer.php`, `TriageTicketJob::maybeReviewRefund()` |
| 9. Prompt injection and HITL | `app/Ai/Tools/RequestRefund.php`, `TriageRunController::approve()`, eval case `prompt-injection-refund` |
| 10. Evals, traces, cost | `app/Evals/EvalRunner.php`, `php artisan ticket-desk:eval`, `triage_runs.trace` |

## Tests

```bash
php artisan test
```

The official Laravel AI SDK fake (`TriageAgent::fake()`) stands in for the model. Help-doc embeddings are hashed locally unless `TICKET_DESK_EMBEDDINGS_ENABLED=true`.

## License

MIT. Copyright (c) 2026 Nnamdi Alexander Akamu.
