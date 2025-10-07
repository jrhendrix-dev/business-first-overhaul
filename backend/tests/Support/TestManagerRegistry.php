<?php
// tests/Support/TestManagerRegistry.php
declare(strict_types=1);

namespace App\Tests\Support;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;

/**
 * Minimal ManagerRegistry implementation for unit tests using an in-memory EntityManager.
 */
final class TestManagerRegistry implements ManagerRegistry
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function getDefaultConnectionName(): string
    {
        return 'default';
    }

    public function getConnection($name = null): Connection
    {
        return $this->em->getConnection();
    }

    /**
     * @return array<string, Connection>
     */
    public function getConnections(): array
    {
        return ['default' => $this->em->getConnection()];
    }

    /**
     * @return array<string, string>
     */
    public function getConnectionNames(): array
    {
        return ['default' => 'default'];
    }

    public function getDefaultManagerName(): string
    {
        return 'default';
    }

    public function getManager($name = null): ObjectManager
    {
        return $this->em;
    }

    /**
     * @return array<string, ObjectManager>
     */
    public function getManagers(): array
    {
        return ['default' => $this->em];
    }

    public function resetManager(?string $name = null): ObjectManager
    {
        // For tests we don't actually reset; just return the existing manager.
        return $this->em;
    }
    /**
     * @param string $alias
     */
    public function getAliasNamespace($alias): string
    {
        throw new \RuntimeException('Alias namespaces are not supported in tests.');
    }

    /**
     * @return array<string, string>
     */
    public function getManagerNames(): array
    {
        return ['default' => 'default'];
    }

    /**
     * @param class-string $persistentObject
     */
    public function getRepository($persistentObject, $persistentManagerName = null): ObjectRepository
    {
        return $this->em->getRepository($persistentObject);
    }

    /**
     * @param class-string $class
     */
    public function getManagerForClass($class): ?ObjectManager
    {
        return $this->em;
    }
}
