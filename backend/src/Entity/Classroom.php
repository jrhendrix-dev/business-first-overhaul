<?php

namespace App\Entity;

use App\Repository\ClassroomRepository;
use App\Entity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ClassroomRepository::class)]
#[ORM\Table(name: "classes")]
class Classroom
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: "id", type: "integer")]
    private ?int $id = null;

    #[ORM\Column(name: "name", type: "string", length: 45)]
    private string $name;

    #[ORM\OneToMany(mappedBy: "classroom", targetEntity: User::class)]
    private Collection $students;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: "teacher_id", referencedColumnName: "id", nullable: true, onDelete: "SET NULL")]
    private ?User $teacher = null;

    public function __construct()
    {
        $this->students = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    // Puedes añadir getters/setters para name, students, teacher...



public function getName(): string
{
return $this->name;
}

public function setName(string $name): void
{
$this->name = $name;
}

/** @return Collection<int, User> */
public function getStudents(): Collection
{
return $this->students;
}

public function addStudent(User $student): void
{
if (!$this->students->contains($student)) {
$this->students->add($student);
$student->setClassroom($this);
}
}

public function removeStudent(User $student): void
{
if ($this->students->removeElement($student)) {
if ($student->getClassroom() === $this) {
$student->setClassroom(null);
}
}
}

public function getTeacher(): ?User
{
return $this->teacher;
}

public function setTeacher(?User $user): void
{
if ($user !== null && $user->getRole() !== UserRole::TEACHER) {
throw new \LogicException('Assigned user is not a teacher.');
}

$this->teacher = $user;
}
}
