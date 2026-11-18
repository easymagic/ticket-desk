<?php

namespace App\Support;

use Laravel\Ai\Embeddings;

/**
 * Turns text into a vector. When embeddings are not configured, a stable
 * hashed vector is used so RAG and tests work without an API key.
 */
class Embedder
{
    public function embed(string $text): array
    {
        if (! config('ticket-desk.embeddings_enabled')) {
            return $this->hashed($text);
        }

        $response = Embeddings::for([$text])->generate();

        return $response->embeddings[0];
    }

    /**
     * @return list<float>
     */
    public function hashed(string $text): array
    {
        $dimensions = (int) config('ticket-desk.embedding_dimensions', 16);
        $vector = array_fill(0, $dimensions, 0.0);
        $tokens = preg_split('/[^a-z0-9]+/i', strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($tokens as $token) {
            $bucket = unpack('N', hash('sha256', $token, true))[1] % $dimensions;
            $vector[$bucket] += 1.0;
        }

        $norm = sqrt(array_sum(array_map(fn ($v) => $v * $v, $vector))) ?: 1.0;

        return array_map(fn ($v) => round($v / $norm, 6), $vector);
    }

    public function cosine(array $a, array $b): float
    {
        $size = min(count($a), count($b));
        if ($size === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $na = 0.0;
        $nb = 0.0;
        for ($i = 0; $i < $size; $i++) {
            $dot += $a[$i] * $b[$i];
            $na += $a[$i] * $a[$i];
            $nb += $b[$i] * $b[$i];
        }

        $denom = sqrt($na) * sqrt($nb);

        return $denom > 0 ? $dot / $denom : 0.0;
    }
}
