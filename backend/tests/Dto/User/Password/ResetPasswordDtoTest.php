<?php
// tests/Dto/User/Password/ResetPasswordDtoTest.php
declare(strict_types=1);

namespace App\Tests\Dto\User\Password;

use App\Dto\User\Password\ResetPasswordDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ResetPasswordDtoTest extends TestCase
{
    private function v()
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    #[Test]
    public function it_validates_happy_path(): void
    {
        // 32-char token (min) and policy-compliant password (>=12 chars, upper, lower, number, special)
        $dto = new ResetPasswordDto(
            token: str_repeat('a', 32),
            newPassword: 'Str0ngPass!ABCdef12'
        );

        self::assertCount(0, $this->v()->validate($dto));
    }

    #[Test]
    public function it_rejects_policy_non_compliant_password(): void
    {
        $dto = new ResetPasswordDto(
            token: str_repeat('b', 32),
            newPassword: 'short' // too short + fails policy
        );

        self::assertGreaterThan(0, $this->v()->validate($dto)->count());
    }

    #[Test]
    public function it_rejects_short_token(): void
    {
        $dto = new ResetPasswordDto(
            token: 'too-short-token',
            newPassword: 'NewPassw0rd!'
        );

        self::assertGreaterThan(0, $this->v()->validate($dto)->count());
    }
}
