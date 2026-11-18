<?php

namespace App\Console\Commands;

use App\Evals\EvalRunner;
use Illuminate\Console\Command;

class RunEvalsCommand extends Command
{
    protected $signature = 'ticket-desk:eval';

    protected $description = 'Run the offline ticket-desk evaluation suite.';

    public function handle(EvalRunner $runner): int
    {
        $results = $runner->run();
        $failed = 0;

        foreach ($results as $result) {
            if ($result['passed']) {
                $this->info('PASS  '.$result['name']);
            } else {
                $failed++;
                $this->error('FAIL  '.$result['name']);
                foreach ($result['failures'] as $failure) {
                    $this->line('  - '.$failure);
                }
            }
        }

        $this->newLine();
        $this->line(count($results).' cases, '.$failed.' failed.');

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
