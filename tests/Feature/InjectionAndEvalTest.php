<?php

namespace Tests\Feature;

use App\Evals\EvalRunner;
use App\Models\RefundRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InjectionAndEvalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedHelpArticles();
    }

    public function test_the_offline_eval_suite_passes(): void
    {
        $results = app(EvalRunner::class)->run();
        $failed = collect($results)->where('passed', false)->all();

        $this->assertSame([], $failed, json_encode($failed, JSON_PRETTY_PRINT));
        $this->assertSame(0, RefundRequest::count());
    }
}
