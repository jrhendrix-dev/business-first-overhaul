<?php
// src/Enum/GradeComponentEnum.php
declare(strict_types=1);

namespace App\Enum;

/**
 * Enumerates the supported grade components.
 */
enum GradeComponentEnum: string
{
    case QUIZ     = 'quiz';
    case PROJECT  = 'project';
    case HOMEWORK = 'homework';
    case EXAM     = 'exam';

    /**
     * Human-readable labels used in API responses.
     */
    public function label(): string
    {
        return match ($this) {
            self::QUIZ     => 'Quiz',
            self::PROJECT  => 'Project',
            self::HOMEWORK => 'Homework',
            self::EXAM     => 'Exam',
        };
    }

    /**
     * @return string[]
     */
    public static function values(): array
    {
        return array_map(static fn(self $case) => $case->value, self::cases());
    }

    /**
     * Normalize an arbitrary string into a GradeComponentEnum.
     * Falls back to null when the string does not match any case.
     */
    public static function tryFromMixed(string $value): ?self
    {
        $normalized = strtolower(trim($value));
        return self::tryFrom($normalized);
    }
}
