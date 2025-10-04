<?php
// tests/Support/EntityIdHelper.php
declare(strict_types=1);

namespace App\Tests\Support;

final class EntityIdHelper
{
    /**
     * Force-set a private/protected "id" on Doctrine entities for unit tests.
     * Do NOT use this outside tests.
     *
     * @param object $entity
     * @param int    $id
     */
    public static function setId(object $entity, int $id): void
    {
        $ro = new \ReflectionObject($entity);

        // Try "id" first
        if ($ro->hasProperty('id')) {
            $prop = $ro->getProperty('id');
            $prop->setAccessible(true);
            $prop->setValue($entity, $id);
            return;
        }

        // Fallback: look for *Id or similar (rare)
        foreach ($ro->getProperties() as $p) {
            if (\preg_match('/^.*id$/i', $p->getName())) {
                $p->setAccessible(true);
                $p->setValue($entity, $id);
                return;
            }
        }

        throw new \RuntimeException(sprintf('Could not find an id property on %s', $ro->getName()));
    }
}
