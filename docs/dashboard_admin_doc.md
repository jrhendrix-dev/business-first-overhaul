# 📄 `dashboard_admin.php` — Admin Dashboard Overview

## Purpose
The admin dashboard provides full CRUD access to users, classes, grades, schedules, and contact form submissions. It is modular, AJAX-powered, and dynamically loads its tabs using PHP partials and JavaScript event bindings.

---

## Features

- ✅ Dynamic tab interface using Bootstrap
- ✅ AJAX-powered CRUD functionality for all entities
- ✅ Real-time dropdowns and inline editing
- ✅ Contact form viewer and responder
- ✅ Role-based behavior and form logic
- ✅ Modular JS for maintainability

---

## Load Order and Flow

### 1. Initialization
```php
require_once __DIR__ . '/../includes/adminHandlers.php';
require_once __DIR__ . '/../src/models/Database.php';
$con = Database::connect();
```

### 2. Preloads Class and Teacher Dropdowns
```php
window.classOptions = `<?= $classOptions ?>`;
window.teacherOptions = `<?= $teacherOptions ?>`;
```

These are injected as JavaScript globals and reused throughout tab interactions.

### 3. Renders Tab Layout via `dashboard_admin.php`
Each tab is populated with HTML from `/src/api/partials/*.php` via `adminHandlers.php`.

---

## Tabs Overview

### 👥 Users Tab
- Loaded by: `adminHandlers.php?loadUsers=1`
- Logic: `usuarios.js`, `UserController.php`
- Key Features:
  - Create user with auto-assigned ID
  - Dropdown adapts to role (student/teacher)
  - Edit/delete inline with AJAX

### 📚 Classes Tab
- Loaded by: `adminHandlers.php?loadClasses=1`
- Logic: `clases.js`, `ClassController.php`
- Key Features:
  - Assign unassigned teacher to class
  - Ensures teachers are not double-assigned
  - Gapless ID logic for new classes

### 📝 Grades Tab
- Loaded by: `adminHandlers.php?loadNotas=1`
- Logic: `notas.js`, `GradesController.php`
- Key Features:
  - Inline grade editing with validation (0-10 or NULL)
  - Auto-inserts `notas` row if missing
  - AJAX saves to `notas` table

### 📅 Schedule Tab
- Loaded by: `adminHandlers.php?loadHorarios=1`
- Logic: `horarios.js`, `ScheduleController.php`
- Key Features:
  - Editable timetable for each weekday
  - Select dropdowns powered by global class list
  - AJAX updates `schedule` table

### 📬 Contact Form Tab
- Loaded by: `adminHandlers.php?action=getFormulario`
- Logic: `formulario.js`, `create.php`
- Key Features:
  - Displays all contact form submissions
  - Reply via mailto link
  - Delete entries dynamically

---

## JavaScript Modules

Each tab has its own dedicated JavaScript file:

| Module         | Purpose                          |
|----------------|----------------------------------|
| `usuarios.js`  | User CRUD, dynamic form logic    |
| `clases.js`    | Class CRUD, teacher assignment   |
| `notas.js`     | Grade inline editing and save    |
| `horarios.js`  | Schedule editing per weekday     |
| `formulario.js`| Load/delete contact form records |

All files use jQuery and are initialized on `$(document).ready()`.

---

## Data Flow Summary

| Action               | Source JS       | Backend Target            | Method | Notes                            |
|----------------------|-----------------|----------------------------|--------|----------------------------------|
| Load users           | usuarios.js     | adminHandlers.php          | GET    | Rendered table HTML             |
| Create/edit user     | usuarios.js     | UserController.php         | POST   | Based on user level             |
| Delete user          | usuarios.js     | UserController.php         | POST   | Deletes + refreshes tab         |
| Load classes         | clases.js       | adminHandlers.php          | GET    | Includes teacher names          |
| Create/edit class    | clases.js       | ClassController.php        | POST   | Assigns/unassigns teacher       |
| Delete class         | clases.js       | ClassController.php        | POST   | Updates dropdowns too           |
| Load grades          | notas.js        | adminHandlers.php          | GET    | Students only                   |
| Save grades          | notas.js        | GradesController.php       | POST   | Validates each input            |
| Load schedules       | horarios.js     | adminHandlers.php          | GET    | Includes all classes            |
| Update schedule      | horarios.js     | ScheduleController.php     | POST   | Updates one row per day         |
| Load contact form    | formulario.js   | adminHandlers.php          | GET    | Lists all submissions           |
| Delete form message  | formulario.js   | adminHandlers.php          | POST   | Deletes one message             |

---

## Security Considerations

- All backend handlers use `prepared statements`.
- User password hashes are stored using `password_hash()`.
- Grade inputs are bounded (0–10 or null).
- Access is gated by session validation (`check_login()`).
- Forms validate role-specific input requirements.

---

## Related Files

- `dashboard_admin.php`: Main layout for admin
- `adminHandlers.php`: AJAX response engine
- `UserController.php`, `ClassController.php`, etc.: Data mutation logic
- `bootstrap.php`: Initializes DB and session
- `index.css`: Shared layout and responsive design

---

## Summary

The admin dashboard is a fully-featured interface combining backend PHP logic, dynamic HTML partials, and jQuery-powered interactivity. It is optimized for responsiveness, security, and extensibility — giving admins full control over academy operations without page reloads.

---

**Author:** Jonathan Ray Hendrix  
[GitHub](https://github.com/jrhendrix-dev) · [LinkedIn](https://linkedin.com/in/jonathan-hendrix-dev)
