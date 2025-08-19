<?php
namespace App\Entity;

use App\Enum\EnrollmentStatusEnum;
use App\Repository\EnrollmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EnrollmentRepository::class)]
#[ORM\Table(name: 'enrollment')]
#[ORM\UniqueConstraint(name: 'uniq_student_classroom', columns: ['student_id','classroom_id'])]
#[UniqueEntity(fields: ['student', 'classroom'], message: 'Student is already enrolled in this classroom.')]
class Enrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $student = null;

    #[ORM\ManyToOne(targetEntity: Classroom::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Classroom $classroom = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $enrolledAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private \DateTimeImmutable $droppedAt;

    #[ORM\Column(type: 'string', enumType: EnrollmentStatusEnum::class)]
    private EnrollmentStatusEnum $status = EnrollmentStatusEnum::ACTIVE;

    /** @var Collection<int, Grade> */
    #[ORM\OneToMany(targetEntity: Grade::class, mappedBy: 'enrollment', cascade: ['persist'], orphanRemoval: true)]
    private Collection $grades;

    public function __construct()
    {
        $this->enrolledAt = new \DateTimeImmutable();
        $this->grades = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getStudent(): ?User { return $this->student; }
    public function setStudent(?User $student): self { $this->student = $student; return $this; }

    public function getClassroom(): ?Classroom { return $this->classroom; }
    public function setClassroom(Classroom $classroom): self { $this->classroom = $classroom; return $this; }

    public function getEnrolledAt(): \DateTimeImmutable { return $this->enrolledAt; }
    public function setEnrolledAt(\DateTimeImmutable $at): self { $this->enrolledAt = $at; return $this; }

    public function getDroppedAt(): \DateTimeImmutable { return $this->droppedAt; }
    public function setDroppedAt(\DateTimeImmutable $at): self { $this->droppedAt = $at; return $this; }

    public function getStatus(): EnrollmentStatusEnum { return $this->status; }
    public function setStatus(EnrollmentStatusEnum $status): self { $this->status = $status; return $this; }

    /** @return Collection<int, Grade> */
    public function getGrades(): Collection { return $this->grades; }
    public function addGrade(Grade $g): self
    {
        if (!$this->grades->contains($g)) {
            $this->grades->add($g);
            $g->setEnrollment($this);
        }
        return $this;
    }
    public function removeGrade(Grade $g): self
    {
        if ($this->grades->removeElement($g) && $g->getEnrollment() === $this) {
            $g->setEnrollment(null);
        }
        return $this;
    }
}
