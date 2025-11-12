<?php
// src/Domain/Classroom/Exception/ClassroomInactiveException.php
declare(strict_types=1);

namespace App\Entity\Exception;

use RuntimeException;

/**
 * Thrown when a write operation is attempted on a non-active classroom.
 */
final class ClassroomInactiveException extends RuntimeException
{
    public function __construct(private readonly string $status)
    {
        parent::__construct('Classroom is not ACTIVE.');
    }

    public function status(): string { return $this->status; }
}
