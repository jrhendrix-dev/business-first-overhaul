<?php
// src/Entity/Grade.php
declare(strict_types=1);

namespace App\Entity;

use App\Repository\GradeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Enum\GradeComponentEnum;

#[ORM\Entity(repositoryClass: GradeRepository::class)]
#[ORM\Table(name: 'grade')]
class Grade
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Enrollment::class, inversedBy: 'grades')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Enrollment $enrollment = null;

    // e.g., “Midterm”, “Final”, “Homework 1”
    #[ORM\Column(type: 'string', enumType: GradeComponentEnum::class, length: 32)]
    #[Assert\NotNull]
    private GradeComponentEnum $component = GradeComponentEnum::QUIZ;


    #[ORM\Column(type: 'float')]
    #[Assert\Range(min: 0)]
    private float $score = 0.0;

    #[ORM\Column(type: 'float')]
    #[Assert\Range(min: 1)]
    private float $maxScore = 10.0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $gradedAt;

    public function __construct()
    {
        $this->gradedAt = new \DateTimeImmutable();
    }

    /**
     * Primary key accessor.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Enrollment getter.
     */
    public function getEnrollment(): ?Enrollment
    {
        return $this->enrollment;
    }

    /**
     * Fluent enrollment setter.
     */
    public function setEnrollment(?Enrollment $enrollment): self
    {
        $this->enrollment = $enrollment;
        return $this;
    }

    /**
     * Grade component enum accessor.
     */
    public function getComponent(): GradeComponentEnum
    {
        return $this->component;
    }

    /**
     * Fluent component setter.
     */
    public function setComponent(GradeComponentEnum $component): self
    {
        $this->component = $component;
        return $this;
    }

    /**
     * Score accessor.
     */
    public function getScore(): float
    {
        return $this->score;
    }

    /**
     * Fluent score setter.
     */
    public function setScore(float $score): self
    {
        $this->score = $score;
        return $this;
    }

    /**
     * Maximum score accessor.
     */
    public function getMaxScore(): float
    {
        return $this->maxScore;
    }

    /**
     * Fluent max-score setter.
     */
    public function setMaxScore(float $max): self
    {
        $this->maxScore = $max;
        return $this;
    }

    /**
     * Graded timestamp accessor.
     */
    public function getGradedAt(): \DateTimeImmutable
    {
        return $this->gradedAt;
    }

    /**
     * Fluent graded timestamp setter.
     */
    public function setGradedAt(\DateTimeImmutable $at): self
    {
        $this->gradedAt = $at;
        return $this;
    }

    /**
     * Percent helper calculated from score/maxScore.
     */
    public function getPercent(): float
    {
        return $this->maxScore > 0 ? ($this->score / $this->maxScore) * 100.0 : 0.0;
    }


}
