<?php
// src/Mapper/Response/UserResponseMapper.php
declare(strict_types=1);

namespace App\Mapper\Response;

use App\Dto\User\MeResponseDto;
use App\Dto\User\UserResponseDto;
use App\Entity\User;
use App\Mapper\Contracts\ResponseMapperInterface;
use App\Repository\ClassroomRepository;

final class UserResponseMapper implements ResponseMapperInterface
{
public function __construct(
private readonly ClassroomRepository $classrooms
) {}

/** Priority order: first match wins */
private const ROLE_PRIORITY = ['ROLE_ADMIN', 'ROLE_TEACHER', 'ROLE_STUDENT'];

/** ✅ Interface implementation: object → DTO */
public function toResponse(object $source): UserResponseDto
{
/** @var User $u */
$u = $source;

return new UserResponseDto(
id:         (int) $u->getId(),
userName:   (string) $u->getUserName(),
email:      (string) $u->getEmail(),
firstName:  (string) $u->getFirstName(),
lastName:   (string) $u->getLastName(),
role:       $u->getRole()?->value,
isActive:   (bool) $u->isActive(),
// If createdAt can be null in fixtures, provide a sane default:
createdAt:  $u->getCreatedAt() ?? new \DateTimeImmutable(),
fullName:   trim((string)$u->getFirstName().' '.(string)$u->getLastName()),
);
}

/**
* ✅ Helper for list endpoints:
* Build a plain array and optionally include classrooms.
*/
public function toArray(User $u, bool $includeClasses = false): array
{
$data = [
'id'        => $u->getId(),
'firstName' => $u->getFirstName(),
'lastName'  => $u->getLastName(),
'userName'  => $u->getUserName(),
'email'     => $u->getEmail(),
'role'      => $u->getRole()?->value ?? $this->primaryRole($u),
'isActive'  => $u->isActive(),
'createdAt' => $u->getCreatedAt()?->format(DATE_ATOM),
'fullName'  => trim((string)$u->getFirstName().' '.(string)$u->getLastName()),
];

if ($includeClasses) {
$classes = [];

// TEACHER: read from classrooms table (your source of truth)
if ($u->isTeacher()) {
foreach ($this->classrooms->findActiveByTeacher($u) as $row) {
$classes[] = [
'id'   => (int) $row['id'],
'name' => (string) $row['name'],
];
}
}

// STUDENT: from enrollments (only active)
if (method_exists($u, 'getEnrollments')) {
foreach ($u->getEnrollments() as $enr) {
$classroom = method_exists($enr, 'getClassroom') ? $enr->getClassroom() : null;
if (!$classroom) { continue; }

$isActive = method_exists($enr, 'isActive') ? (bool)$enr->isActive() : true;
if (!$isActive) { continue; }

$classes[] = [
'id'   => $classroom->getId(),
'name' => $classroom->getName(),
];
}
}

$data['classrooms'] = $classes;
}

return $data;
}

public function toMeResponse(object $source): MeResponseDto
{
/** @var User $u */
$u = $source;

return new MeResponseDto(
id: (int) $u->getId(),
email: $u->getEmail(),
roles: $u->getRoles(),
firstName: $u->getFirstName(),
lastName: $u->getLastName(),
primaryRole: $this->primaryRole($u),
);
}

private function primaryRole(User $u): ?string
{
$have = $u->getRoles();
foreach (self::ROLE_PRIORITY as $r) {
if (\in_array($r, $have, true)) {
return $r;
}
}
return null;
}
}
