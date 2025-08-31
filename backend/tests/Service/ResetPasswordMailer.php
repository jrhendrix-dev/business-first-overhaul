<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\User;
use App\Service\ResetPasswordMailer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

#[CoversClass(ResetPasswordMailer::class)]
final class ResetPasswordMailerTest extends TestCase
{
    #[Test]
    public function send_builds_expected_email_and_sends(): void
    {
        $mailer = $this->createMock(MailerInterface::class);

        $sut = new ResetPasswordMailer(
            $mailer,
            appName: 'Business First',
            fromEmail: 'no-reply@businessfirstacademy.net',
            frontendResetUrl: 'https://businessfirstacademy.net/reset-password'
        );

        $user = new User();
        $user->setEmail('john@example.com');
        $this->setEntityId($user, 42);

        $mailer->expects(self::once())
            ->method('send')
            ->with(self::callback(function (TemplatedEmail $email) use ($user) {
                self::assertSame(['john@example.com' => null], $email->getTo());
                self::assertSame(['no-reply@businessfirstacademy.net' => null], $email->getFrom());
                self::assertSame('[Business First] Password reset request', $email->getSubject());
                self::assertSame('emails/password_reset.html.twig', $email->getHtmlTemplate());

                $ctx = $email->getContext();
                self::assertSame($user, $ctx['user']);
                self::assertSame('Business First', $ctx['appName']);
                self::assertStringContainsString('reset-password?token=', (string)$ctx['resetUrl']);
                self::assertStringContainsString('&uid=42', (string)$ctx['resetUrl']);

                return true;
            }));

        $sut->send($user, 'abcd1234');
    }

    /** Helper to set a private auto-generated ID on entities for testing. */
    private function setEntityId(object $entity, int $id): void
    {
        $ref = new \ReflectionObject($entity);
        if ($ref->hasProperty('id')) {
            $prop = $ref->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($entity, $id);
        }
    }
}
