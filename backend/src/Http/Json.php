<?php
// src/Http/Json.php
namespace App\Http;
use Symfony\Component\HttpFoundation\Request;

final class Json
{
    /**
     * @throws \JsonException
     */
    public static function body(Request $r): array
    {
        $raw = $r->getContent() ?: '{}';
        return json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    }
}
