<?php
declare(strict_types=1);

namespace App\Repository;

use App\Entity\BlogPost;
use App\Enum\BlogPostStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * BlogPost repository.
 *
 * @extends ServiceEntityRepository<BlogPost>
 */
final class BlogPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BlogPost::class);
    }

    public function save(BlogPost $post): void
    {
        $em = $this->getEntityManager();
        $em->persist($post);
        $em->flush();
    }

    public function remove(BlogPost $post): void
    {
        $em = $this->getEntityManager();
        $em->remove($post);
        $em->flush();
    }

    public function findById(int $id): ?BlogPost
    {
        return $this->find($id);
    }

    public function findBySlug(string $slug): ?BlogPost
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    /**
     * @return BlogPost[]
     */
    public function listForAdmin(?BlogPostStatus $status = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')->addSelect('a')
            ->orderBy('p.createdAt', 'DESC');

        if ($status) {
            $qb->andWhere('p.status = :st')->setParameter('st', $status);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return BlogPost[]
     */
    public function listPublished(int $limit = 20, int $offset = 0): array
    {
        return $this->createQueryBuilder('p')
            ->leftJoin('p.author', 'a')->addSelect('a')
            ->andWhere('p.status = :st')->setParameter('st', BlogPostStatus::PUBLISHED)
            ->orderBy('p.publishedAt', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }
}
