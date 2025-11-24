# Business First Overhaul

This monorepo contains the **Symfony 7 backend** and planned **Angular + Tailwind** SPA.

The goal: replace the legacy PHP/MySQL site with a modern, tested, Dockerized architecture that is production-ready and recruiter-authentic.

---


## 🔧 Stack

- **Backend**: [Symfony 7](https://symfony.com/) (PHP 8.3), Doctrine ORM, PHPUnit 12
- **Frontend**: Angular 17 + Tailwind CSS (WIP)
- **Database**: MySQL 8
- **DevOps**: Docker Compose, GitHub Actions CI, Codecov coverage

---

## 📂 Structure

```text
business-first-overhaul/
├── backend/      # Symfony API (fully covered by tests, rate-limiter, honeypot)
├── frontend/     # Angular + Tailwind app (planned / WIP)
├── docker-compose.yml
└── .github/      # GitHub Actions workflows
```

---

## 🧠 Backend architecture (Symfony API)

### Routing overview

* Attribute-based controllers live under `App\Controller`, and [`config/routes.yaml`](backend/config/routes.yaml) applies `/api`, `/api/teacher`, and `/api/admin` prefixes to keep concerns isolated while sharing a single kernel.
* Security firewalls expose `POST /api/login` for JSON login while higher-level controllers orchestrate business workflows.

### Domain model

* Core aggregates such as [`User`](backend/src/Entity/User.php), [`Classroom`](backend/src/Entity/Classroom.php), [`Enrollment`](backend/src/Entity/Enrollment.php), and [`Grade`](backend/src/Entity/Grade.php) capture invariants like role transitions, teacher assignment, enrollment uniqueness, and grade validation.
* Supporting entities including [`AccountToken`](backend/src/Entity/AccountToken.php) and enum value objects in [`backend/src/Enum`](backend/src/Enum) provide secure token storage and bounded sets for domain rules.

### Data transfer objects (DTOs)

* DTOs live in [`backend/src/Dto`](backend/src/Dto) and are grouped by feature (Classroom, Grade, User, etc.). They define typed payloads decorated with Symfony validator constraints so controllers receive normalized, validated inputs.
* Projection DTOs (for example, `GradeViewDto` and `StudentClassroomItemDto`) provide audience-specific read models that hide or reveal fields appropriately.

### Mapper layer

* Request mappers in [`backend/src/Mapper/Request`](backend/src/Mapper/Request) translate JSON into DTOs, converting enums and throwing domain-specific validation exceptions when inputs are malformed.
* Response mappers in [`backend/src/Mapper/Response`](backend/src/Mapper/Response) shape entities into consistent API payloads for students, teachers, and admins.

### Services & business workflows

* Manager classes such as [`ClassroomManager`](backend/src/Service/ClassroomManager.php), [`EnrollmentManager`](backend/src/Service/EnrollmentManager.php), [`GradeManager`](backend/src/Service/GradeManager.php), and [`UserManager`](backend/src/Service/UserManager.php) encapsulate transactional rules like ownership checks, idempotent enrollment, cascading role changes, and aggregate persistence.
* Cross-cutting services (`AccountTokenService`, `AccountMailer`, `RequestEntityResolver`, etc.) centralize token issuance, outbound messaging, and lookup helpers to keep controllers thin.

### Messaging & async flows

* Messages in [`backend/src/Message`](backend/src/Message) capture asynchronous work such as contact form forwarding and email change notifications. Handlers in [`backend/src/MessageHandler`](backend/src/MessageHandler) enforce rate limiting and idempotency before sending email.

### Validation & error handling

* [`ValidationException`](backend/src/Http/Exception/ValidationException.php) standardizes 422 responses so clients always receive `{ error: { code, details } }` payloads.
* Controllers lean on DTO constraints and manager-level guards to surface clear conflict errors when domain rules fail.

### Testing strategy

* Functional tests under [`backend/tests/Functional`](backend/tests/Functional) exercise authenticated HTTP flows via helper traits that perform JWT login and seed fixtures.
* Unit tests under [`backend/tests/Unit`](backend/tests/Unit) cover DTO factories, mappers, services, and entity behaviors to keep regression scope tight.

---

## 📡 API surface

### Public & authentication endpoints

| Method | Path | Route name | Purpose |
| --- | --- | --- | --- |
| GET | `/api/classrooms` | `classroom_list_public` | List active classrooms with teacher summaries. |
| GET | `/api/classrooms/{id}` | `classroom_get_public` | Retrieve a single classroom (dropped classes return 404). |
| GET | `/api/classrooms/search` | `classroom_search_public` | Search classrooms by name. |
| GET | `/api/enrollments/class/{classId}/active-enrollments` | `active_enrollments_list_public` | Show active enrollments for a classroom. |
| POST | `/api/auth/register` | `auth_register` | Register a new student account. |
| POST | `/api/password/forgot` | `password_forgot` | Trigger password reset email flow. |
| POST | `/api/password/reset` | `password_reset` | Complete a password reset with a valid token. |
| POST | `/api/contact` | `api_contact` | Submit the public contact form (rate limited). |
| POST | `/api/login` | `api_login` | Authenticate via JSON login for token issuance. |
| GET | `/api/test-auth` | `api_test_authapp_test_test` | Echo authenticated principal details for diagnostics. |

### Shared authenticated endpoints (`ROLE_USER`)

| Method | Path | Route name | Purpose |
| --- | --- | --- | --- |
| GET | `/api/users` | `users_search` | Search users by name with optional role filtering. |
| GET | `/api/users/{id}` | `users_get_by_id` | Retrieve a lightweight user profile. |

### Self-service endpoints (`/api/me`)

| Method | Path | Route name | Purpose |
| --- | --- | --- | --- |
| GET | `/api/me` | `me_get` | Return the authenticated user's profile. |
| PATCH | `/api/me` | `me_update` | Update profile fields (name, phone, etc.). |
| POST | `/api/me/change-password` | `me_change_password` | Change the authenticated user's password. |
| POST | `/api/me/change-email` | `me_email_change_start` | Start an email change by issuing a confirmation token. |
| GET | `/api/me/change-email/confirm` | `me_email_change_confirm` | Confirm an email change token. |
| GET | `/api/me/grades` | `me_grades` | List grades for the student's current classroom. |
| GET | `/api/me/grades/all` | `me_grades_all` | Aggregate all grades across the student's enrollments. |
| GET | `/api/me/classrooms` | `me_classroom_enrolled_in_public` | List classrooms the student is enrolled in. |

### Teacher endpoints (`/api/teacher`)

| Method | Path | Route name | Purpose |
| --- | --- | --- | --- |
| GET | `/api/teacher/classes` | `teacher_classes_list` | List classrooms owned by the authenticated teacher. |
| GET | `/api/teacher/classes/{classId}/students` | `teacher_class_roster` | Fetch the roster for a class with optional filters. |
| GET | `/api/teacher/classes/{classId}/students/{studentId}/grades` | `teacher_grades_list` | List grades for a specific enrollment. |
| POST | `/api/teacher/classes/{classId}/students/{studentId}/grades` | `teacher_grades_create` | Create a grade for a student in the teacher's class. |
| GET | `/api/teacher/grades/{id}` | `teacher_grades_get` | Retrieve a single grade owned by the teacher. |
| PUT/PATCH | `/api/teacher/grades/{id}` | `teacher_grades_update` | Update a grade's scores or components. |
| DELETE | `/api/teacher/grades/{id}` | `teacher_grades_delete` | Delete a grade from the teacher's classroom. |
| GET | `/api/teacher/classes/{classId}/grades` | `teacher_class_grades` | Aggregate grades for one classroom. |
| GET | `/api/teacher/classes/grades` | `teacher_all_classes_grades` | Aggregate grades across all of the teacher's classrooms. |

### Admin endpoints (`/api/admin`)

| Method | Path | Route name | Purpose |
| --- | --- | --- | --- |
| GET | `/api/admin/classrooms` | `admin_classroom_list` | List classrooms (active and dropped) with teacher data. |
| GET | `/api/admin/classrooms/{id}` | `admin_classroom_get` | Retrieve classroom details including enrollment counts. |
| GET | `/api/admin/classrooms/taught-by/{id}` | `admin_classrooms_taught_by` | List classrooms taught by a specific teacher. |
| GET | `/api/admin/classrooms/taught-by-count/{id}` | `admin_classrooms_taught_by_count` | Count classrooms taught by a specific teacher. |
| GET | `/api/admin/classrooms/unassigned` | `admin_classroom_unassigned` | List classrooms without assigned teachers. |
| GET | `/api/admin/classrooms/search` | `admin_classroom_search_by_name` | Search classrooms by name (including dropped). |
| PUT | `/api/admin/classrooms/{id}/teacher` | `admin_classroom_assign_teacher` | Assign or reassign a teacher to a classroom. |
| POST | `/api/admin/classrooms/{id}/reactivate` | `admin_classroom_reactivate` | Reactivate a dropped classroom. |
| POST | `/api/admin/classrooms` | `admin_classroom_create` | Create a new classroom. |
| PUT | `/api/admin/classrooms/{id}` | `admin_classroom_rename` | Rename an existing classroom. |
| DELETE | `/api/admin/classrooms/{id}/teacher` | `admin_classroom_unassign_teacher` | Remove the teacher assignment from a classroom. |
| DELETE | `/api/admin/classrooms/{id}` | `admin_classroom_delete` | Delete or archive a classroom depending on enrollment state. |
| PUT | `/api/admin/enrollments/class/{classId}/student/{studentId}` | `admin_enrollments_enroll` | Enroll or reactivate a student in a class. |
| DELETE | `/api/admin/enrollments/class/{classId}/student/{studentId}` | `admin_enrollments_soft_drop` | Soft drop a student's enrollment. |
| DELETE | `/api/admin/enrollments/class/{classId}/student/{studentId}/hard` | `admin_enrollments_drop` | Hard drop a student's enrollment. |
| DELETE | `/api/admin/enrollments/class/{classId}/enrollments` | `admin_enrollments_drop_all` | Drop all active enrollments for a class. |
| GET | `/api/admin/enrollments/class/{classId}/enrollments` | `admin_enrollments_list` | List enrollments for a class (any status). |
| GET | `/api/admin/enrollments/class/{classId}/active-enrollments` | `admin_active_enrollments_list` | List only active enrollments for a class. |
| GET | `/api/admin/grades/enrollments/{enrollmentId}/grades` | `admin_grades_list` | List grades for an enrollment. |
| POST | `/api/admin/grades/enrollments/{enrollmentId}/grades` | `admin_grades_create` | Create a grade for an enrollment. |
| GET | `/api/admin/grades/all` | `admin_grades_all` | Aggregate all grades across enrollments. |
| GET | `/api/admin/grades/{id}` | `admin_grades_get` | Retrieve a grade by identifier. |
| PUT/PATCH | `/api/admin/grades/{id}` | `admin_grades_update` | Update grade values for any enrollment. |
| DELETE | `/api/admin/grades/{id}` | `admin_grades_delete` | Delete a grade. |
| GET | `/api/admin/students/{id}/classrooms` | `admin_student_active_classrooms` | List active classrooms for a student. |
| GET | `/api/admin/users` | `admin_users_list` | List users with pagination and filters. |
| POST | `/api/admin/users` | `admin_users_create` | Create a user with role assignment. |
| PATCH | `/api/admin/users/{id}` | `admin_users_update` | Update an existing user's attributes. |
| DELETE | `/api/admin/users/{id}` | `admin_users_delete` | Delete a user account. |
| GET | `/api/admin/users/{id}` | `admin_users_get` | Retrieve a user by identifier. |
| GET | `/api/admin/users/by-email` | `admin_users_by_email` | Look up a user by email address. |
| GET | `/api/admin/users/search-in-classroom` | `admin_users_in_classroom` | Search for users assigned to a specific classroom. |
| GET | `/api/admin/users/recently-registered` | `admin_users_recent` | List recently registered accounts. |
| GET | `/api/admin/users/students` | `admin_users_students` | List all student accounts. |
| GET | `/api/admin/users/teachers` | `admin_users_teachers` | List all teacher accounts. |
| GET | `/api/admin/users/students/without-classroom` | `admin_students_without_classroom` | Surface students without classroom assignments. |
| GET | `/api/admin/users/teachers/without-classroom` | `admin_teachers_without_classroom` | Surface teachers without classroom assignments. |
| GET | `/api/admin/users/count-by-role` | `admin_users_count_by_role` | Return counts of users grouped by role. |
| POST | `/api/admin/users/classroom/{classroomId}/unassign-all` | `admin_unassign_all_students` | Unassign all students from a classroom. |

---

## 🚀 Getting started

### Clone & build
```bash
git clone https://github.com/jrhendrix-dev/business-first-overhaul.git
cd business-first-overhaul
docker compose up --build
```

### Run tests
```bash
docker compose exec backend composer test
docker compose exec backend composer test:cov
```

Coverage reports are generated to `backend/var/coverage-html/`.

---

## ✅ CI & Coverage

Every push to `main` runs PHPUnit inside Docker via GitHub Actions:

- [CI workflow runs](https://github.com/jrhendrix-dev/business-first-overhaul/actions/workflows/ci.yml)
- [Coverage dashboard (Codecov)](https://codecov.io/gh/jrhendrix-dev/business-first-overhaul)
