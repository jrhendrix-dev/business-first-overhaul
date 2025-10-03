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
    #[Test]
    public function it_validates_happy_path(): void
    {
        $token = \str_repeat('a', 64);
        $dto = new ResetPasswordDto(
            token: $token,
            newPassword: 'StrongPassw0rd!'
        );

        $violations = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator()->validate($dto);
        self::assertCount(0, $violations);
    }

    #[Test]
    public function it_requires_policy_compliant_password(): void
    {
        $token = \str_repeat('a', 64);
        $dto = new ResetPasswordDto(
            token: $token,
            newPassword: 'short'
        );

        $violations = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator()->validate($dto);
        self::assertGreaterThan(0, $violations->count());
    }
}
