<?php

namespace App\Ai\Middleware;

use Closure;
use Laravel\Ai\Gateway\StepResult;
use Laravel\Ai\PendingStep;

class BudgetGuard
{
    public function __construct(
        private int $maxInputTokens = 16000,
        private int $maxSteps = 8,
    ) {}

    public function handle(PendingStep $step, Closure $next): mixed
    {
        if ($step->usage->inputTokens >= $this->maxInputTokens) {
            return StepResult::stop();
        }

        if (property_exists($step, 'number') && $step->number >= $this->maxSteps) {
            return StepResult::stop();
        }

        return $next($step);
    }
}
