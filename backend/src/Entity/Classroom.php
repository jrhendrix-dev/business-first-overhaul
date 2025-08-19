<?php

namespace App\Entity;

use App\Repository\ClassroomRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use App\Enum\UserRoleEnum;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

/**
 * Represents a classroom within the Business First English Academy system.
 *
 * Each classroom can have a name, a teacher, and multiple student users assigned to it.
 */
#[ORM\Entity(repositoryClass: ClassroomRepository::class)]
#[ORM\Table(name: "classes")]
class Classroom
{
    /**
     * Unique identifier for the classroom.
     *
     * @var int|null
     */
    #[Groups(['classroom:read'])]
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id = null;

    /**
     * Name of the classroom.
     *
     * @var string
     */
    #[Groups(['classroom:read'])]
    #[ORM\Column(name: "name", type: "string", length: 45)]
    private string $name;

    #[ORM\OneToMany(
        targetEntity: Enrollment::class,
        mappedBy: 'classroom',
        cascade: ['persist', 'remove'],
        orphanRemoval: false
    )]
    private Collection $enrollments;

    public function __construct()
    {
        $this->enrollments = new ArrayCollection();
    }

    /** @return Collection<int, Enrollment> */
    public function getEnrollments(): Collection
    {
        return $this->enrollments;
    }

    // a quick read-only list of students:
    /** @return array<\App\Entity\User> */
    public function getStudents(): array
    {
        return array_map(
            static fn (Enrollment $e) => $e->getStudent(),
            $this->enrollments->toArray()
        );
    }

    /**
     * The teacher assigned to the classroom.
     *
     * @var User|null
     */
    #[Groups(['classroom:read'])]
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "teacher_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    #[MaxDepth(1)]
    private ?User $teacher = null;



    public function setId(?int $id): void {
        $this->id = $id;
    }

    /**
     * Gets the classroom ID.
     *
     * @return int|null
     */
    public function getId(): ?int {
        return $this->id;
    }

    /**
     * Gets the classroom name.
     *
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * Sets the classroom name.
     *
     * @param string $name
     */
    public function setName(string $name): void {
        $this->name = $name;
    }


    /**
     * Gets the teacher assigned to this classroom.
     *
     * @return User|null
     */
    public function getTeacher(): ?User {
        return $this->teacher;
    }

    /**
     * Assigns a teacher to the classroom, validating their role.
     *
     * @param User|null $user
     *
     * @throws \LogicException if the assigned user is not a teacher
     */
    public function setTeacher(?User $user): void {
        if ($user !== null && $user->getRole() !== UserRoleEnum::TEACHER) {
            throw new \LogicException('Assigned user is not a teacher.');
        }
        $this->teacher = $user;
    }


}
