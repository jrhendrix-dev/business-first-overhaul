<?php
/**
 * adminHandlers.php
 *
 * AJAX and utility handlers for the Business First English Center admin dashboard.
 * Responsibilities include:
 *   - Dynamic dropdowns for classes and teachers
 *   - Data retrieval for users, classes, grades, schedules, and contact forms
 *   - Outputting HTML snippets in response to AJAX requests
 *
 * PHP version 7+
 *
 * @package   BusinessFirstEnglishCenter
 * @author    Jonathan Ray Hendrix <jrhendrixdev@gmail.com>
 * @license   MIT LICENSE
 */

// ============================================================================
// INITIALIZATION: Establish database connection
// ============================================================================

if (!isset($con)) {
    require_once __DIR__ . '/../src/models/Database.php';
    try {
        $con = Database::connect();
    } catch (Exception $e) {
        // Optional: log error or notify admin
    }
}

// ============================================================================
// DROPDOWN: Get unassigned classes (for teacher form)
// ============================================================================

/**
 * AJAX: Return <option> elements for all unassigned classes.
 * If a `GET['include']` class ID is passed, that class will be shown even if assigned.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['availableClasses'])) {
    $includeClassId = isset($_GET['include']) ? intval($_GET['include']) : 0;

    $query = "
        SELECT classid, classname FROM clases
        WHERE classid NOT IN (
            SELECT class FROM users
            WHERE ulevel = 2 AND class IS NOT NULL AND class != $includeClassId
        )
    ";

    if ($includeClassId > 0) {
        $query .= " UNION SELECT classid, classname FROM clases WHERE classid = $includeClassId";
    }

    $query .= " ORDER BY classname ASC";
    $result = $con->query($query);

    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['classid']}'>" . htmlspecialchars($row['classname'] ?? '') . "</option>";
    }
    exit;
}

// ============================================================================
// DROPDOWN: Get available teachers
// ============================================================================

/**
 * AJAX: Return <option> elements for teachers not assigned to any class.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['availableTeachers'])) {
    $query = "SELECT user_id, username FROM users WHERE ulevel = 2 AND (class IS NULL OR class = '' OR class = '0')";
    $result = $con->query($query);

    while ($row = $result->fetch_assoc()) {
        echo "<option value='{$row['user_id']}'>" . htmlspecialchars($row['username'] ?? '') . "</option>";
    }
    exit;
}

// ============================================================================
// PRELOAD: Classes and teachers for JavaScript usage
// ============================================================================

/** @var string $classOptionsJS Preloaded HTML <option> string for all classes (JSON encoded) */
$classOptions = "";
$classResult = $con->query("SELECT classid, classname FROM clases ORDER BY classid ASC");
if ($classResult && $classResult->num_rows > 0) {
    while ($row = $classResult->fetch_assoc()) {
        $classOptions .= "<option value='{$row['classid']}'>" . htmlspecialchars($row['classname'] ?? '') . "</option>";
    }
}
$classOptionsJS = json_encode($classOptions);

/** @var string $teacherOptionsJS Preloaded HTML <option> string for all teachers (JSON encoded) */
$teacherOptions = "";
$profResult = $con->query("SELECT user_id, username FROM users WHERE ulevel = 2 ORDER BY username ASC");
if ($profResult && $profResult->num_rows > 0) {
    while ($row = $profResult->fetch_assoc()) {
        $teacherOptions .= "<option value='{$row['user_id']}'>" . htmlspecialchars($row['username'] ?? '') . "</option>";
    }
}
$teacherOptionsJS = json_encode($teacherOptions);

// ============================================================================
// USERS: Load user table
// ============================================================================

/**
 * AJAX: Load all users and return an HTML table.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['loadUsers'])) {
    $res = $con->query("SELECT users.*, clases.classname FROM users LEFT JOIN clases ON users.class = clases.classid ORDER BY users.user_id ASC");

    if ($res && $res->num_rows > 0) {
        echo "<table class='table table-striped'><thead><tr>
                <th>ID</th><th>Username</th><th>Email</th><th>Class</th><th>Level</th><th>Actions</th>
              </tr></thead><tbody>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr data-id='{$row['user_id']}'>
                    <td data-label='id: '>{$row['user_id']}</td>
                    <td class='username' data-label='user: '>" . htmlspecialchars($row['username'] ?? '') . "</td>
                    <td class='email' data-label='email: '>" . htmlspecialchars($row['email'] ?? '') . "</td>
                    <td class='class' data-classid='{$row['class']}' data-label='class: '>" . htmlspecialchars($row['classname'] ?? '') . "</td>
                    <td class='ulevel' data-label='level: '>{$row['ulevel']}</td>
                    <td>
                        <button class='btn btn-sm btn-warning edit-btn edit-btn-class'>Edit</button>
                        <button class='btn btn-sm btn-danger delete-btn'>Delete</button>
                    </td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No users found.</p>";
    }
    exit;
}

// ============================================================================
// CLASSES: Load class table
// ============================================================================

/**
 * AJAX: Load all classes and return an HTML table.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['loadClasses'])) {
    $res = $con->query("SELECT * FROM clases LEFT JOIN users ON clases.classid=users.class AND users.ulevel='2'");

    if ($res && $res->num_rows > 0) {
        echo "<table class='table table-striped'><thead><tr>
                <th>ID</th><th>Course Name</th><th>Teacher</th><th>Actions</th>
              </tr></thead><tbody>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr data-id='{$row['classid']}'>
                    <td data-label='id: '>{$row['classid']}</td>
                    <td class='classname' data-label='class: '>" . htmlspecialchars($row['classname']) . "</td>
                    <td class='profesor' data-label='teacher: ' data-profid='{$row['user_id']}'>" . htmlspecialchars($row['username'] ?? '') . "</td>
                    <td>
                        <button class='btn btn-sm btn-warning edit-class-btn edit-btn-class'>Edit</button>
                        <button class='btn btn-sm btn-danger delete-class-btn'>Delete</button>
                    </td>
                  </tr>";
        }
        echo "</tbody></table>";

    } else {
        echo "<p>No classes found.</p>";
    }
    exit;
}

// ============================================================================
// GRADES: Load student grades
// ============================================================================

/**
 * AJAX: Load student grades and return an HTML table.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['loadNotas'])) {
    $res = $con->query("
        SELECT u.user_id, u.username, c.classname, n.Nota1, n.Nota2, n.Nota3
        FROM users u
        LEFT JOIN clases c ON u.class = c.classid
        LEFT JOIN notas n ON u.user_id = n.idAlumno
        WHERE u.ulevel = 3
        ORDER BY c.classname, u.username
    ");

    if ($res && $res->num_rows > 0) {
        echo "<table class='table table-striped'><thead><tr>
                <th>Student</th><th>Class</th><th>Grade 1</th><th>Grade 2</th><th>Grade 3</th><th>Actions</th>
              </tr></thead><tbody>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr data-id='{$row['user_id']}'>
                    <td class='alumno' data-label='student: '>" . htmlspecialchars($row['username'] ?? '') . "</td>
                    <td class='curso' data-label='class: '>" . htmlspecialchars($row['classname'] ?? '') . "</td>
                    <td class='nota1' data-label='grade 1: '>" . htmlspecialchars($row['Nota1'] ?? '') . "</td>
                    <td class='nota2' data-label='grade 2: '>" . htmlspecialchars($row['Nota2'] ?? '') . "</td>
                    <td class='nota3' data-label='grade 3: '>" . htmlspecialchars($row['Nota3'] ?? '') . "</td>
                    <td>
                        <button class='btn btn-sm btn-warning edit-nota-btn edit-btn-class'>Edit</button>
                        <button class='btn btn-sm btn-success save-nota-btn d-none'>Save</button>
                        <button class='btn btn-sm btn-secondary cancel-nota-btn d-none'>Cancel</button>
                    </td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No grades found.</p>";
    }
    exit;
}

// ============================================================================
// SCHEDULES: Load class schedules
// ============================================================================

/**
 * AJAX: Load weekly schedule and return an HTML table.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['loadHorarios'])) {
    $res = $con->query("
        SELECT s.day_id, s.week_day,
               c1.classname AS firstclass_name,
               c2.classname AS secondclass_name,
               c3.classname AS thirdclass_name
        FROM schedule s
        LEFT JOIN clases c1 ON s.firstclass = c1.classid
        LEFT JOIN clases c2 ON s.secondclass = c2.classid
        LEFT JOIN clases c3 ON s.thirdclass = c3.classid
        ORDER BY s.day_id ASC
    ");

    if ($res && $res->num_rows > 0) {
        echo "<table class='table table-striped'><thead><tr>
                <th>Day</th><th>First Class</th><th>Second Class</th><th>Third Class</th><th>Actions</th>
              </tr></thead><tbody>";
        while ($row = $res->fetch_assoc()) {
            echo "<tr data-id='{$row['day_id']}'>
                <td class='weekday' data-label='day: '>" . htmlspecialchars($row['week_day'] ?? '') . "</td>
                <td class='firstclass' data-label='class 1: '>" . ($row['firstclass_name'] ?? '') . "</td>
                <td class='secondclass' data-label='class 2: '>" . ($row['secondclass_name'] ?? '') . "</td>
                <td class='thirdclass' data-label='class 3: '>" . ($row['thirdclass_name'] ?? '') . "</td>
                <td>
                    <button class='btn btn-sm btn-warning edit-horario-btn edit-btn-class'>Edit</button>
                    <button class='btn btn-sm btn-success save-horario-btn d-none'>Save</button>
                    <button class='btn btn-sm btn-secondary cancel-horario-btn d-none'>Cancel</button>
                </td>
              </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No schedules found.</p>";
    }
    exit;
}

// ============================================================================
// CONTACT FORM: Load contact form submissions
// ============================================================================

/**
 * AJAX: Retrieve all contact form submissions.
 */
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'getFormulario') {
    $result = $con->query("SELECT * FROM formulario ORDER BY submitted_at DESC");

    if ($result && $result->num_rows > 0) {
        echo "<table class='table table-striped'><thead>
                <tr><th hidden>ID</th><th>Name</th><th>Surname</th><th>Phone</th><th>Email</th><th>Message</th><th>Date</th><th>Actions</th></tr>
              </thead><tbody>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr data-id='{$row['idformulario']}'>
                    <td hidden>" . htmlspecialchars($row['idformulario']) . "</td>
                    <td>" . htmlspecialchars($row['nombre']) . "</td>
                    <td>" . htmlspecialchars($row['apellidos']) . "</td>
                    <td>" . htmlspecialchars($row['teléfono']) . "</td>
                    <td>" . htmlspecialchars($row['email']) . "</td>
                    <td>" . nl2br(htmlspecialchars($row['mensaje'])) . "</td>
                    <td>" . htmlspecialchars($row['submitted_at']) . "</td>
                    <td>
                        <a href='mailto:" . htmlspecialchars($row['email']) .
                "?subject=Consulta%20desde%20el%20formulario&body=Hola%20" . urlencode($row['nombre']) . ",%0A%0AGracias%20por%20contactar.%0A%0AResponderemos%20a%20la%20mayor%20brevedad.' class='btn btn-sm btn-primary' title='Reply'>
                            <i class='fas fa-envelope'></i>
                        </a>
                        <button class='btn btn-sm btn-danger delete-formtab-btn'>Delete</button>
                    </td>
                  </tr>";
        }
        echo "</tbody></table>";
    } else {
        echo "<p>No contact form submissions yet.</p>";
    }

    exit;
}
