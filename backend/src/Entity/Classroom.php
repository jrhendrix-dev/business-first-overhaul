<?php

namespace App\Entity;

use App\Enum\ClassroomStatusEnum;
use App\Enum\UserRoleEnum;
use App\Entity\Enrollment;
use App\Entity\User;
use App\Repository\ClassroomRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: ClassroomRepository::class)]
#[ORM\Table(name: 'classes')]
#[ORM\UniqueConstraint(name: "uniq_class_name", columns: ["name"])]
#[UniqueEntity(fields: ['name'], message: 'A classrooms with this name already exists.')]
class Classroom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: "name", type: "string", length: 45, unique: true)]
    private string $name;

    #[ORM\OneToMany(
        targetEntity: Enrollment::class,
        mappedBy: 'classroom',
        cascade: ['persist', 'remove'],
        orphanRemoval: false
    )]
    private Collection $enrollments;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'teacher_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    #[MaxDepth(1)]
    private ?User $teacher = null;

    #[ORM\Column(type: 'string', enumType: ClassroomStatusEnum::class)]
    private ClassroomStatusEnum $status = ClassroomStatusEnum::ACTIVE;

    #[ORM\Column(type: 'json', options: ['jsonb' => true])]
    private array $meta = []; // generic bag

    public function __construct()
    {
        $this->enrollments = new ArrayCollection();
    }

    // ---------- Collections ----------

    /** @return Collection<int, Enrollment> */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    /** @return self */
    public function addEnrollment(Enrollment $enrollment): self
    {
        if (!$this->enrollments->contains($enrollment)) {
            $this->enrollments->add($enrollment);
            $enrollment->setClassroom($this);
        }
        return $this;
    }

    /** @return self */
    public function removeEnrollment(Enrollment $enrollment): self
    {
        if ($this->enrollments->removeElement($enrollment) && $enrollment->getClassroom() === $this) {
            $enrollment->setClassroom(null);
        }
        return $this;
    }

    /** @return array<User> quick read-only list of students */
    public function getStudents(): array
    {
        return array_map(
            static fn (Enrollment $e) => $e->getStudent(),
            $this->enrollments->toArray()
        );
    }

    // ---------- Scalar fields ----------

    public function getId(): ?int
    {
        return $this->id;
    }

    // (Generally you should not set ID manually; keeping typeless setter out.)
    // If you really need it for tests, change to: public function setId(?int $id): self { $this->id = $id; return $this; }

    public function getName(): string
    {
        return $this->name;
    }

    /** @return self */
    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }


    // ---------- Associations ----------

    public function getTeacher(): ?User
    {
        return $this->teacher;
    }

    /**
     * @throws \LogicException if the assigned user is not a teacher
     * @return self
     */
    public function setTeacher(?User $user): self
    {
        if ($user !== null && $user->getRole() !== UserRoleEnum::TEACHER) {
            throw new \LogicException('Assigned user is not a teacher.');
        }
        $this->teacher = $user;
        return $this;
    }

    public function getStatus(): ClassroomStatusEnum
    {
        return $this->status;
    }

    /** @return self */
    public function setStatus(ClassroomStatusEnum $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function isDropped(): bool
    {
        return $this->status === ClassroomStatusEnum::DROPPED;
    }

    public function getMeta(): array { return $this->meta; }
    public function setMeta(array $meta): self { $this->meta = $meta; return $this; }
    public function isRestoreBannerDismissed(): bool { return (bool)($this->meta['restoreBannerDismissed'] ?? false); }
    public function dismissRestoreBanner(): self { $this->meta['restoreBannerDismissed'] = true; return $this; }
    public function resetRestoreBanner(): self { unset($this->meta['restoreBannerDismissed']); return $this; }
}
