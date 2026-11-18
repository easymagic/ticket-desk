<?php

namespace Tests;

use App\Models\HelpArticle;
use App\Support\Embedder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function authHeaders(): array
    {
        return ['Authorization' => 'Bearer '.config('ticket-desk.api_token')];
    }

    protected function seedHelpArticles(): void
    {
        $embedder = app(Embedder::class);
        foreach (['returns', 'shipping', 'exchanges', 'damaged'] as $slug) {
            $body = file_get_contents(resource_path("help/{$slug}.md"));
            HelpArticle::updateOrCreate(
                ['slug' => $slug],
                [
                    'title' => ucfirst($slug),
                    'body' => $body,
                    'embedding' => $embedder->embed($slug.' '.$body),
                ]
            );
        }
    }
}
