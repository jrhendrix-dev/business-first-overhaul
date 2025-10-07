<?php

namespace App\Repository;

use App\Entity\PasswordResetToken;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PasswordResetToken>
 */
final class PasswordResetTokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PasswordResetToken::class);
    }

    /**
     * Invalidate all old tokens for a user (optional hardening).
     */
    public function invalidateAllForUser(User $user): int
    {
        return $this->createQueryBuilder('t')
            ->update()
            ->set('t.usedAt', ':now')
            ->where('t.user = :u')
            ->andWhere('t.usedAt IS NULL')
            ->setParameter('now', new \DateTime())
            ->setParameter('u', $user)
            ->getQuery()
            ->execute();
    }

    /**
     * Find a usable (unused, not expired) token for the given user and hash.
     */
    public function findUsable(User $user, string $tokenHash): ?PasswordResetToken
    {
        /** @var PasswordResetToken|null $row */
        $row = $this->createQueryBuilder('t')
            ->andWhere('t.user = :u')->setParameter('u', $user)
            ->andWhere('t.tokenHash = :h')->setParameter('h', $tokenHash)
            ->andWhere('t.usedAt IS NULL')
            ->getQuery()
            ->getOneOrNullResult();

        return $row && $row->isUsable() ? $row : null;
    }
}
