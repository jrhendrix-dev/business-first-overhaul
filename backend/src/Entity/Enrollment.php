<?php

namespace App\Entity;

use App\Enum\EnrollmentStatusEnum;
use App\Repository\EnrollmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: EnrollmentRepository::class)]
#[ORM\Table(name: 'enrollment')]
#[ORM\UniqueConstraint(name: 'uniq_student_classroom', columns: ['student_id', 'classroom_id'])]
#[UniqueEntity(fields: ['student', 'classroom'], message: 'Student is already enrolled in this classroom.')]
class Enrollment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?User $student = null;

    #[ORM\ManyToOne(targetEntity: Classroom::class, inversedBy: 'enrollments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Classroom $classroom = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $enrolledAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $droppedAt = null;

    #[ORM\Column(type: 'string', enumType: EnrollmentStatusEnum::class)]
    private EnrollmentStatusEnum $status = EnrollmentStatusEnum::ACTIVE;

    /** @var Collection<int, Grade> */
    #[ORM\OneToMany(targetEntity: Grade::class, mappedBy: 'enrollment', cascade: ['persist'], orphanRemoval: true)]
    private Collection $grades;

    public function __construct()
    {
        $this->enrolledAt = new \DateTimeImmutable();
        $this->grades     = new ArrayCollection();
    }

    // ----------- Scalars -----------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEnrolledAt(): \DateTimeImmutable
    {
        return $this->enrolledAt;
    }

    public function setEnrolledAt(\DateTimeImmutable $at): self
    {
        $this->enrolledAt = $at;
        return $this;
    }

    public function getDroppedAt(): ?\DateTimeImmutable
    {
        return $this->droppedAt;
    }

    public function setDroppedAt(?\DateTimeImmutable $at): self
    {
        $this->droppedAt = $at;
        return $this;
    }

    public function getStatus(): EnrollmentStatusEnum
    {
        return $this->status;
    }

    public function setStatus(EnrollmentStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    // ----------- Associations -----------

    /** @return User */
    public function getStudent(): User
    {
        if ($this->student === null) {
            throw new \LogicException('Invariant violated: Enrollment has no student.');
        }
        return $this->student;
    }

    public function setStudent(?User $student): self
    {
        $this->student = $student;
        return $this;
    }

    /** @return Classroom */
    public function getClassroom(): Classroom
    {
        if ($this->classroom === null) {
            throw new \LogicException('Invariant violated: Enrollment has no classroom.');
        }
        return $this->classroom;
    }

    public function setClassroom(?Classroom $classroom): self
    {
        $this->classroom = $classroom;
        return $this;
    }

    /** @return Collection<int, Grade> */
    public function getGrades(): Collection
    {
        return $this->grades;
    }

    public function addGrade(Grade $grade): self
    {
        if (!$this->grades->contains($grade)) {
            $this->grades->add($grade);
            $grade->setEnrollment($this);
        }
        return $this;
    }

    public function removeGrade(Grade $grade): self
    {
        if ($this->grades->removeElement($grade) && $grade->getEnrollment() === $this) {
            $grade->setEnrollment(null);
        }
        return $this;
    }

    // ----------- Convenience (optional) -----------

    /** Soft-drop this enrollment. */
    public function drop(?\DateTimeImmutable $when = null): self
    {
        $this->status    = EnrollmentStatusEnum::DROPPED;
        $this->droppedAt = $when ?? new \DateTimeImmutable();
        return $this;
    }

    /** Reactivate a previously dropped enrollment and reset enrolledAt timestamp. */
    public function reactivate(?\DateTimeImmutable $when = null): self
    {
        $this->status     = EnrollmentStatusEnum::ACTIVE;
        $this->droppedAt  = null;
        $this->enrolledAt = $when ?? new \DateTimeImmutable();
        return $this;
    }
}
