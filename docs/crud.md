
# CRUD Operations Documentation

This document provides a comprehensive breakdown of the core CRUD functionality in the **Business First English Center** project. The goal is to facilitate understanding, maintenance, and further development by clearly explaining how user, class, grade, and schedule data is created, updated, and deleted through the project's PHP and JavaScript files.

---

## Table of Contents

1. [create.php](#createphp)
2. [update.php](#updatephp)
3. [delete.php](#deletephp)
4. [adminHandlers.php](#adminhandlersphp)
5. [usuarios.js](#usuariosjs)
6. [clases.js](#clasesjs)

---

## create.php

**Purpose**: Handles creation of users, classes, grades, and schedules.

**Location**: `views/create.php`

**Responsibilities**:
- Receives AJAX POST requests from the frontend JavaScript.
- Inserts new records into the appropriate database tables.
- Automatically assigns unique, gap-free IDs using a gap-filling algorithm.

**Key Operations**:
- `createUser`: Adds a new user and creates a `notas` entry if level = 3 (student).
- `createClass`: Adds a new class.
- `createSchedule`: Adds a weekly schedule for a specific day.
- ID assignment logic ensures no gaps in IDs (e.g. 1, 2, 4 -> next ID = 3).

**Security**:
- Uses prepared statements to prevent SQL injection.
- Assumes input validation is done on the client-side.

---

## update.php

**Purpose**: Updates existing users, classes, grades, and schedules.

**Location**: `views/update.php`

**Responsibilities**:
- Handles AJAX requests for all update operations.
- Ensures data integrity across related tables (e.g., syncing teacher-class assignment).

**Key Operations**:
- `updateUser`: Updates user profile, including reassignment of class or role.
- `updateClass`: Updates class name and teacher. Also unassigns the class from previous teachers.
- `updateNota`: Updates student grades or inserts if not present.
- `updateHorario`: Changes daily class schedules.

**Advanced Features**:
- Dynamic grade validation (`nota1`, `nota2`, `nota3`) to accept `null` or 0-10 values.
- Auto-create `notas` row when user is promoted to student.
- Auto-delete `notas` row if user is no longer a student (handled separately).

---

## delete.php

**Purpose**: Deletes users, classes, and schedules.

**Location**: `views/delete.php`

**Responsibilities**:
- Responds to AJAX calls to delete entries safely.
- Removes associated `notas` when a student user is deleted.

**Key Operations**:
- `deleteUser`: Deletes a user and cascade-deletes their grades.
- `deleteClass`: Deletes a class.
- `deleteSchedule`: Deletes a schedule for a day.

**Safety**:
- Uses `LIMIT 1` for user deletion.
- Strong input sanitation on the client side.

---

## adminHandlers.php

**Purpose**: Shared logic utilities for the admin dashboard tabs.

**Location**: `views/adminHandlers.php`

**Responsibilities**:
- Provides server-side rendering of HTML snippets for dynamic tab loading.
- Encapsulates logic for:
  - User management (load all users)
  - Class management (load all classes)
  - Grade table generation
  - Dynamic dropdowns (e.g. available classes for new teachers)

**Notable Features**:
- Used heavily by JavaScript via jQuery’s `load()` method.
- Cleanly separates view-rendering from raw AJAX responses.

---

## usuarios.js

**Purpose**: Frontend logic for managing users.

**Location**: `public/assets/js/usuarios.js`

**Responsibilities**:
- Handles creation, update, and deletion of users via AJAX.
- Binds UI form submissions and buttons to server actions.
- Refreshes dropdowns and feedback dynamically.

**Structure**:
- Uses jQuery to handle form submissions.
- Updates DOM dynamically upon AJAX response.
- Performs client-side validations and user feedback.

**Cohesion with Server**:
- Tightly coupled with `create.php`, `update.php`, `delete.php`, and `adminHandlers.php`.

---

## clases.js

**Purpose**: Frontend logic for managing classes.

**Location**: `public/assets/js/clases.js`

**Responsibilities**:
- Allows creation and editing of class names and teacher assignments.
- Dynamically reloads class data and refreshes form dropdowns.

**Highlights**:
- Class creation avoids duplicate names.
- Dropdowns only list unassigned teachers (to avoid duplicates).
- Calls `adminHandlers.php` to regenerate class list HTML.

---


## Final Notes

- All CRUD operations are modular and invoked via AJAX using jQuery.
- PHP files are responsibly split for each CRUD type.
- Security practices like prepared statements and password hashing are implemented.
- JavaScript files are tightly scoped to specific dashboard sections.
- Communication follows a clean request-response pattern using jQuery.

For full project context and front-end structure, see [`dashboard_admin.php`](./dashboard_admin.md) and index layout documentation.

---

**Author**: Jonathan Ray Hendrix  
**LinkedIn**: [jonathan-hendrix-dev](https://www.linkedin.com/in/jonathan-hendrix-dev)  
**GitHub**: [jrhendrix-dev](https://github.com/jrhendrix-dev)
