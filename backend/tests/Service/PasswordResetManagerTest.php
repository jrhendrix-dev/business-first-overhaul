<?php
declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use App\Repository\PasswordResetTokenRepository;
use App\Service\Contracts\PasswordResetTokenPort;
use App\Service\PasswordResetManager;
use DateInterval;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[CoversClass(PasswordResetManager::class)]
final class PasswordResetManagerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private EntityManagerInterface $em;

    /** @var PasswordResetTokenPort&MockObject */
    private PasswordResetTokenPort $tokens;

    /** @var UserPasswordHasherInterface&MockObject */
    private UserPasswordHasherInterface $hasher;

    private PasswordResetManager $sut;

    protected function setUp(): void
    {
        $this->em     = $this->createMock(EntityManagerInterface::class);
        $this->tokens = $this->createMock(PasswordResetTokenPort::class);
        $this->hasher = $this->createMock(UserPasswordHasherInterface::class);

        $this->sut = new PasswordResetManager($this->em, $this->tokens, $this->hasher, 3600);
    }

    #[Test]
    public function issue_invalidates_previous_tokens_persists_and_returns_plain_hex(): void
    {
        $user = (new User())->setEmail('a@b.c');

        $this->tokens->expects($this->once())
            ->method('invalidateAllForUser')
            ->with($user);

        $this->em->expects($this->once())
            ->method('persist')
            ->with(self::callback(function ($entity) use ($user) {
                \assert($entity instanceof PasswordResetToken);
                self::assertSame($user, $entity->getUser());
                self::assertNotEmpty($entity->getTokenHash());
                self::assertInstanceOf(DateTimeImmutable::class, $entity->getCreatedAt());
                self::assertInstanceOf(DateTimeImmutable::class, $entity->getExpiresAt());
                self::assertGreaterThan($entity->getCreatedAt(), $entity->getExpiresAt());
                return true;
            }));

        $this->em->expects($this->once())->method('flush');

        $plain = $this->sut->issue($user, '127.0.0.1');

        self::assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $plain);
    }

    #[Test]
    public function consume_hashes_password_marks_token_used_and_flushes(): void
    {
        $user = (new User())->setEmail('a@b.c');

        $token = new PasswordResetToken();
        $token->setUser($user);
        $token->setTokenHash(hash('sha256', 'plain-token'));
        $now = new DateTimeImmutable();
        $token->setCreatedAt($now);
        $token->setExpiresAt($now->add(new DateInterval('PT2H'))); // valid

        $this->tokens->expects($this->once())
            ->method('findUsable')
            ->with($user, hash('sha256', 'plain-token'))
            ->willReturn($token);

        $this->hasher->expects($this->once())
            ->method('hashPassword')
            ->with($user, 'NEW_PASS')
            ->willReturn('HASHED');

        $this->em->expects($this->once())->method('flush');

        $this->sut->consume($user, 'plain-token', 'NEW_PASS');

        self::assertSame('HASHED', $user->getPassword());
        self::assertNotNull($token->getUsedAt());
    }

    #[Test]
    public function consume_throws_when_token_invalid_or_expired(): void
    {
        $user = (new User())->setEmail('a@b.c');

        $this->tokens->method('findUsable')->willReturn(null);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid or expired token.');

        $this->sut->consume($user, 'whatever', 'pass');
    }
}
