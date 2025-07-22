# Business First English Center — Documentation

A complete web-based management system for an English academy focused on corporate language training.

---

## Overview

This system is designed to manage users, classes, grades, and schedules within an educational institution. Built with core PHP, MySQL, JavaScript, and Bootstrap, it demonstrates modular MVC-inspired architecture, strong input validation, and AJAX-based interactivity.

- **GitHub**: [github.com/jrhendrix-dev](https://github.com/jrhendrix-dev)
- **LinkedIn**: [linkedin.com/in/jonathan-hendrix-dev](https://www.linkedin.com/in/jonathan-hendrix-dev)
- **Live Site**: [businessfirstacademy.net](https://businessfirstacademy.net/)

---

## Project Structure

```
Business-First-English-Center/
├── public/                # Public entry point (index.php), login, assets
│   ├── index.php         # Home routing logic
│   ├── login.php         # AJAX login API
│   ├── logout.php        # Session cleanup
│   ├── login_screen.php  # Visible login UI
│   └── assets/           # Static files (CSS, JS, images)
├── src/
│   ├── api/              # Role-based dashboards and partial UI renderers
│   │   ├── dashboard_admin.php
│   │   ├── dashboard_student.php
│   │   ├── dashboard_teacher.php
│   │   └── partials/
│   │       ├── tab_users.php
│   │       ├── tab_classes.php
│   │       ├── tab_grades.php
│   │       ├── tab_schedule.php
│   │       └── tab_formulario.php
│   ├── controllers/      # Modular backend logic
│   │   ├── UserController.php
│   │   ├── ClassController.php
│   │   ├── GradesController.php
│   │   ├── ScheduleController.php
│   │   └── FormTabController.php
│   ├── models/           # Database access and schema
│   │   ├── Database.php
│   │   └── academy_db.sql
│   ├── views/            # Main layout and visual includes
│   │   ├── dashboard.php
│   │   ├── footer.php
│   │   ├── header.php
│   │   └── home.php
│   └── includes/         # Session, handlers, and role validation
│       ├── functions.php
│       ├── auth.php
│       ├── adminHandlers.php
│       ├── studentHandlers.php
│       └── teacherHandlers.php
├── docs/                 # Technical documentation
│   ├── phpdoc/           # HTML output from phpDocumentor/Doctum
│   └── cache/            # Doctum cache
├── bootstrap.php         # Global session and DB bootstrap
├── doctum.php            # Doctum configuration
├── composer.json         # Doc-related dependency file
├── composer.lock
├── README.md
└── documentation.md
```

---

## File and Folder Purposes

### `/public/`
- `index.php`: Entry point; handles redirects based on session role.
- `login.php`: AJAX credential checker.
- `logout.php`: Ends session and redirects.
- `login_screen.php`: Login interface.
- `assets/`: JavaScript, styles, images.

### `/src/api/`
- `dashboard_admin.php`, `dashboard_teacher.php`, `dashboard_student.php`: Role-specific views.
- `partials/`: Server-rendered fragments for Bootstrap tabs.

### `/src/controllers/`
- `UserController.php`, `ClassController.php`, `GradesController.php`, `ScheduleController.php`, `FormTabController.php`: Each handles CRUD logic for a data type (users, classes, grades, schedules, etc.).

### `/src/models/`
- `Database.php`: PDO wrapper for shared DB access.
- `academy_db.sql`: MySQL schema.

### `/src/views/`
- `dashboard.php`: Legacy full layout.
- `footer.php`, `header.php`, `home.php`: Layout components.

### `/src/includes/`
- `functions.php`: Session and helper utilities.
- `auth.php`: Middleware for role verification.
- `adminHandlers.php`, `studentHandlers.php`, `teacherHandlers.php`: AJAX route responders.

### `/bootstrap.php`
- Initializes session.
- Connects to the database.
- Loads authentication and helper functions.
- Included in every page to ensure consistent setup.

### `/docs/`
- `phpdoc/`: Generated documentation.
- `cache/`: Doctum cache metadata.

---

## Key Features

- ✅ Secure login system with password hashing
- ✅ AJAX-based CRUD for users, classes, grades, schedules
- ✅ Modular JS and controller structure by domain
- ✅ Dynamic Bootstrap-based tabs powered by partials
- ✅ Grade auto-linking on user role changes
- ✅ Role-specific dashboards and logic
- ✅ Global initialization via `bootstrap.php`
- ✅ HTML and PHPDoc documentation

---

## Deployment

The system is hosted and publicly accessible at [https://businessfirstacademy.net/](https://businessfirstacademy.net/)

### Recommended Deployment Steps:

1. **Production Server Requirements**:
    - Apache or Nginx
    - PHP 8.x
    - MySQL

2. **Security Measures**:
    - `.htaccess` protection for internal folders
    - HTTPS with secure cookies via `bootstrap.php`

3. **Database Setup**:
    - Run `academy_db.sql` on production DB
    - Update credentials in the `Database.php` connector or `.env`

4. **Routing Setup**:
    - Entry point: `/public/index.php`
    - Views routed via `/src/api/dashboard_*.php`
    - Tab HTML from `/src/api/partials/*.php`

---

## Documentation Tools (Optional)

If using **Doctum**:
- `doctum.php`: Configuration file
- `bootstrap.php`: Session + DB + helper loader
- `composer.json`: Dependencies for doc generation

Ignore in Git:
```bash
/doctum.phar
/doctum.php
/composer.lock
/composer.json
/docs/cache/
/docs/phpdoc/
```

---

## Credits

Developed by **Jonathan Ray Hendrix**  
Email: jrhendrixdev@gmail.com  
LinkedIn: [jonathan-hendrix-dev](https://www.linkedin.com/in/jonathan-hendrix-dev)  
GitHub: [jrhendrix-dev](https://github.com/jrhendrix-dev)

---
