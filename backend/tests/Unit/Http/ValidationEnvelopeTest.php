<?php
// tests/Unit/Http/ValidationEnvelopeTest.php
declare(strict_types=1);

namespace App\Tests\Unit\Http;

use App\Http\Exception\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ValidationEnvelopeTest extends TestCase
{
    #[Test]
    public function envelope_matches_contract(): void
    {
        $ex = new ValidationException(['email' => 'Invalid']);
        $payload = $ex->toPayload();

        self::assertSame(
            ['error' => ['code' => 'VALIDATION_FAILED', 'details' => ['email' => 'Invalid']]],
            $payload
        );
    }
}
