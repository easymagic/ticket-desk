<?php

namespace App\Support;

use App\Models\HelpArticle;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HelpSearch
{
    public function __construct(private Embedder $embedder) {}

    /**
     * @return list<array{id:int,slug:string,title:string,snippet:string,score:float}>
     */
    public function search(string $query, int $limit = 5, float $minScore = 0.15): array
    {
        $vector = $this->embedder->embed($query);

        if (Schema::getConnection()->getDriverName() === 'pgsql' && Schema::hasColumn('help_articles', 'embedding_vector')) {
            $literal = '['.implode(',', $vector).']';
            $rows = HelpArticle::query()
                ->select('*')
                ->selectRaw('1 - (embedding_vector <=> ?::vector) as score', [$literal])
                ->whereNotNull('embedding_vector')
                ->orderByRaw('embedding_vector <=> ?::vector', [$literal])
                ->limit($limit)
                ->get();

            return $rows
                ->filter(fn ($article) => (float) $article->score >= $minScore)
                ->map(fn ($article) => $this->hit($article, (float) $article->score, $query))
                ->values()
                ->all();
        }

        return HelpArticle::query()
            ->whereNotNull('embedding')
            ->get()
            ->map(function (HelpArticle $article) use ($vector, $query) {
                $score = $this->embedder->cosine($vector, $article->embedding ?? []);

                return $this->hit($article, $score, $query);
            })
            ->filter(fn (array $hit) => $hit['score'] >= $minScore)
            ->sortByDesc('score')
            ->take($limit)
            ->values()
            ->all();
    }

    private function hit(HelpArticle $article, float $score, string $query): array
    {
        return [
            'id' => $article->id,
            'slug' => $article->slug,
            'title' => $article->title,
            'snippet' => $this->snippet($article->body, $query),
            'score' => round($score, 4),
        ];
    }

    private function snippet(string $body, string $query): string
    {
        $plain = preg_replace('/\s+/', ' ', $body) ?? $body;
        $terms = preg_split('/\s+/', strtolower($query)) ?: [];
        $pos = 0;
        foreach ($terms as $term) {
            if ($term === '') {
                continue;
            }
            $found = stripos($plain, $term);
            if ($found !== false) {
                $pos = max(0, $found - 40);
                break;
            }
        }

        $slice = trim(substr($plain, $pos, 220));

        return (strlen($plain) > 220 ? $slice.'…' : $slice);
    }
}
