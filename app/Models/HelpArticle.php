<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HelpArticle extends Model
{
    protected $fillable = ['slug', 'title', 'body', 'embedding'];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }
}
