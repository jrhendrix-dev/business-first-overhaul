<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ContactMailer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mailer\Envelope;

final class ContactMailerValidationTest extends TestCase
{
    #[Test]
    public function send_throws_when_message_is_empty(): void
    {
        $fakeMailer = new class() implements MailerInterface {
            /** @var RawMessage[] */
            public array $sent = [];
            public function send(RawMessage $message, Envelope $envelope = null): void
            {
                $this->sent[] = $message;
            }
        };

        $service = new ContactMailer($fakeMailer, 'admin@academy.test', 'Admissions');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('message must not be empty');

        $service->send([
            'id'         => 'cmsg_empty',
            'name'       => 'Alice',
            'email'      => 'alice@example.com',
            'subject'    => 'Hi',
            'message'    => '',              // <-- invalid
            'consent'    => true,
            'ip'         => null,
            'user_agent' => null,
        ]);
    }

    /**
     * Sends the contact message to the academy recipient.
     *
     * @param array{
     *   id:string, name:string, email:string, subject:string, message:string,
     *   consent:bool, ip: ?string, user_agent $payload :?string
     * } $payload Normalized contact data.
     *
     * @throws \InvalidArgumentException If email or message are invalid.
     */
    public function send(array $payload): void
    {
        if (trim($payload['message']) === '') {
            throw new \InvalidArgumentException('message must not be empty');
        }
        // optional hardening against header injection
        if (str_contains($payload['name'], "\r") || str_contains($payload['name'], "\n")) {
            throw new \InvalidArgumentException('invalid name');
        }

    }
}
