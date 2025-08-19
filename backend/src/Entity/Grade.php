<?php
namespace App\Entity;

use App\Repository\GradeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

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
    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $component;

    // score/maxScore (0..maxScore)
    #[ORM\Column(type: 'float')]
    #[Assert\Range(min: 0)]
    private float $score;

    #[ORM\Column(type: 'float')]
    #[Assert\Range(min: 1)]
    private float $maxScore = 10.0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $gradedAt;

    public function __construct()
    {
        $this->gradedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getEnrollment(): ?Enrollment { return $this->enrollment; }
    public function setEnrollment(?Enrollment $enrollment): self { $this->enrollment = $enrollment; return $this; }

    public function getComponent(): string { return $this->component; }
    public function setComponent(string $component): self { $this->component = $component; return $this; }

    public function getScore(): float { return $this->score; }
    public function setScore(float $score): self { $this->score = $score; return $this; }

    public function getMaxScore(): float { return $this->maxScore; }
    public function setMaxScore(float $max): self { $this->maxScore = $max; return $this; }

    public function getGradedAt(): \DateTimeImmutable { return $this->gradedAt; }
    public function setGradedAt(\DateTimeImmutable $at): self { $this->gradedAt = $at; return $this; }

    public function getPercent(): float
    {
        return $this->maxScore > 0 ? ($this->score / $this->maxScore) * 100.0 : 0.0;
    }
}
