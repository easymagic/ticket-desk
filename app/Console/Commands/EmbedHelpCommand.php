<?php

namespace App\Console\Commands;

use App\Models\HelpArticle;
use App\Support\Embedder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EmbedHelpCommand extends Command
{
    protected $signature = 'ticket-desk:embed-help';

    protected $description = 'Re-embed help articles using the configured embedder.';

    public function handle(Embedder $embedder): int
    {
        $articles = HelpArticle::query()->get();
        foreach ($articles as $article) {
            $vector = $embedder->embed($article->title."\n".$article->body);
            $article->update(['embedding' => $vector]);

            if (Schema::getConnection()->getDriverName() === 'pgsql' && Schema::hasColumn('help_articles', 'embedding_vector')) {
                $literal = '['.implode(',', $vector).']';
                DB::update('update help_articles set embedding_vector = ?::vector where id = ?', [$literal, $article->id]);
            }

            $this->line("Embedded {$article->slug}");
        }

        $this->info($articles->count().' articles embedded.');

        return self::SUCCESS;
    }
}
