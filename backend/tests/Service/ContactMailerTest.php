<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Service\ContactMailer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\RawMessage;
use Symfony\Component\Mailer\Envelope;

#[CoversClass(\App\Service\ContactMailer::class)]
final class ContactMailerTest extends TestCase
{
    #[Test]
    public function send_builds_email_with_reply_to(): void
    {
        // Anonymous fake Mailer that just collects messages.
        $fakeMailer = new class() implements MailerInterface {
            /** @var RawMessage[] */
            public array $sent = [];

            public function send(RawMessage $message, Envelope $envelope = null): void
            {
                $this->sent[] = $message;
            }
        };

        $service = new ContactMailer($fakeMailer, 'admin@academy.test', 'Admissions');

        $service->send([
            'id'         => 'cmsg_test',
            'name'       => 'Alice',
            'email'      => 'alice@example.com',
            'subject'    => 'Hi',
            'message'    => 'Body here',
            'consent'    => true,
            'ip'         => null,
            'user_agent' => null,
        ]);

        // Assertions
        self::assertCount(1, $fakeMailer->sent);

        /** @var TemplatedEmail $email */
        $email = $fakeMailer->sent[0];
        self::assertInstanceOf(TemplatedEmail::class, $email);

        // headers / addressing
        self::assertSame('[Contact] Hi', $email->getSubject());
        self::assertEquals(new Address('admin@academy.test', 'Admissions'), $email->getTo()[0]);

        self::assertSame('Alice', $email->getReplyTo()[0]->getName());
        self::assertSame('alice@example.com', $email->getReplyTo()[0]->getAddress());

        // template + context
        self::assertSame('emails/contact.html.twig', $email->getHtmlTemplate());

        $ctx = $email->getContext();
        self::assertSame('cmsg_test',         $ctx['id']);
        self::assertSame('Alice',             $ctx['sender_name']);
        self::assertSame('alice@example.com', $ctx['sender_email']);
        self::assertSame('Hi',                $ctx['subject']);
        self::assertSame('Body here',         $ctx['message']);
        self::assertTrue($ctx['consent']);
        self::assertNull($ctx['ip']);
        self::assertNull($ctx['user_agent']);
    }
}
