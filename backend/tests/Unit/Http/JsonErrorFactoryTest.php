<?php
declare(strict_types=1);

namespace App\Tests\Unit\Http;

use App\Http\JsonErrorFactory;
use PHPUnit\Framework\TestCase;

final class JsonErrorFactoryTest extends TestCase
{
    public function test_make_builds_payload(): void
    {
        $factory = new JsonErrorFactory();
        $payload = $factory->make('BAD_REQUEST', ['field' => 'missing']);

        self::assertSame(['error' => ['code' => 'BAD_REQUEST', 'details' => ['field' => 'missing']]], $payload);
    }
}
