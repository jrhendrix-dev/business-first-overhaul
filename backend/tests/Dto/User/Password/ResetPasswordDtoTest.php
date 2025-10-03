<?php

declare(strict_types=1);

namespace App\Tests\Dto\User\Password;

use App\Dto\User\Password\ResetPasswordDto;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class ResetPasswordDtoTest extends TestCase
{
    private function makeValidator()
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()   // <— was enableAnnotationMapping()
            ->getValidator();
    }

    #[Test]
    public function it_validates_happy_path(): void
    {
        $v = $this->makeValidator();
        $dto = new ResetPasswordDto(
            currentPassword: 'CorrectHorseBatteryStaple1!',
            newPassword:     'NewPassw0rd!',
            confirmPassword: 'NewPassw0rd!'
        );

        $violations = $v->validate($dto);
        self::assertCount(0, $violations);
    }

    #[Test]
    public function it_requires_policy_compliant_password(): void
    {
        $v = $this->makeValidator();
        $dto = new ResetPasswordDto(
            currentPassword: 'CorrectHorseBatteryStaple1!',
            newPassword:     'short',
            confirmPassword: 'short'
        );

        $violations = $v->validate($dto);
        self::assertGreaterThan(0, $violations->count());
    }
}
