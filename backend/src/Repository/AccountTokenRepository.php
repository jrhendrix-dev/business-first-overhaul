<?php
// src/Repository/AccountTokenRepository.php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\AccountToken;
use App\Enum\AccountTokenType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class AccountTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry) { parent::__construct($registry, AccountToken::class); }

    public function findValidByHash(string $hash, AccountTokenType $type): ?AccountToken
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.tokenHash = :h')->setParameter('h', $hash)
            ->andWhere('t.type = :type')->setParameter('type', $type)
            ->andWhere('t.usedAt IS NULL')
            ->andWhere('t.expiresAt > :now')->setParameter('now', new \DateTimeImmutable())
            ->getQuery()->getOneOrNullResult();
    }

    /** Optional: invalidate all outstanding tokens for a user/type (prevents reuse) */
    public function invalidateAllForUser(int $userId, AccountTokenType $type): int
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->update(AccountToken::class, 't')
            ->set('t.usedAt', ':now')
            ->where('t.type = :type')
            ->andWhere('IDENTITY(t.user) = :uid')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('type', $type)
            ->setParameter('uid', $userId)
            ->getQuery()->execute();
    }
}
