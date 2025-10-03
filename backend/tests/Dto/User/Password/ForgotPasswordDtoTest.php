<?php
// tests/Dto/User/Password/ForgotPasswordDtoTest.php
declare(strict_types=1);

namespace App\Tests\Dto\User\Password;

use App\Dto\User\Password\ForgotPasswordDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;


final class ForgotPasswordDtoTest extends TestCase
{
    #[Test]
    public function it_validates_happy_path(): void
    {
        $dto = new ForgotPasswordDto('user@example.com');
        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($dto);
        self::assertCount(0, $violations);
    }

    #[Test]
    public function it_rejects_invalid_email(): void
    {
        $dto = new ForgotPasswordDto('nope');
        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($dto);
        self::assertGreaterThan(0, $violations->count());
    }
}
