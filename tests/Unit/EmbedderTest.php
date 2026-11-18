<?php

namespace Tests\Unit;

use App\Support\Embedder;
use Tests\TestCase;

class EmbedderTest extends TestCase
{
    public function test_hashed_embeddings_are_stable_and_similar_for_related_text(): void
    {
        $embedder = app(Embedder::class);
        $a = $embedder->hashed('refund policy after 30 days');
        $b = $embedder->hashed('refund policy after 30 days');
        $c = $embedder->hashed('refunds and returning items after delivery');
        $d = $embedder->hashed('banana smoothie recipes');

        $this->assertSame($a, $b);
        $this->assertGreaterThan($embedder->cosine($a, $d), $embedder->cosine($a, $c));
    }
}
