<?php

namespace App\Ai\Tools;

trait EncodesJson
{
    protected function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
}
