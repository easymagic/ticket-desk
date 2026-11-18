<?php

namespace App\Ai\Tools;

use App\Models\TriageRun;
use App\Shop\ShopClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Laravel\Ai\Approvals\Approval;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class RequestRefund implements Approvable, Tool
{
    use EncodesJson;
    use InteractsWithApprovals;

    public function __construct(private ShopClient $shop, private ?TriageRun $run = null) {}

    public function description(): Stringable|string
    {
        return 'Request a refund against an order. This is irreversible once approved and always requires a human operator to approve it first.';
    }

    protected function needsApproval(Request $request): Approval|bool
    {
        $amount = (int) ($request['amount_cents'] ?? 0);
        $orderId = $request['order_id'] ?? '?';

        return Approval::required("Refund of {$amount} cents on order {$orderId} needs an operator's approval.");
    }

    public function handle(Request $request): Stringable|string
    {
        $validated = $request->validate([
            'order_id' => 'required|integer|min:1',
            'amount_cents' => 'required|integer|min:1',
            'reason' => 'required|string|min:5|max:1000',
            'ticket_id' => 'nullable|integer|min:1',
        ]);

        try {
            return $this->json($this->shop->requestRefund(
                (int) $validated['order_id'],
                (int) $validated['amount_cents'],
                $validated['reason'],
                isset($validated['ticket_id']) ? (int) $validated['ticket_id'] : $this->run?->ticket_id,
                $this->run?->id,
            ));
        } catch (ModelNotFoundException) {
            return $this->json(['error' => 'Order not found.']);
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'order_id' => $schema->integer()->min(1)->required(),
            'amount_cents' => $schema->integer()->min(1)->required(),
            'reason' => $schema->string()->required(),
            'ticket_id' => $schema->integer()->min(1),
        ];
    }
}
