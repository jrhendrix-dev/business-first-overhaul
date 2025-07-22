<?php
/**
 * dashboard_admin.php
 *
 * Admin dashboard main view for Business First English Center.
 * Handles user, class, grade, schedule, and contact form management.
 * Injects PHP <option> lists into JavaScript for dynamic dropdowns.
 *
 * @package BusinessFirstEnglishCenter
 * @author Jonathan Ray Hendrix <jrhendrixdev@gmail.com>
 * @license MIT
 */

// ========================== DEPENDENCIES ==========================
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../src/controllers/UserController.php';
require_once __DIR__ . '/../../src/controllers/ClassController.php';
require_once __DIR__ . '/../../src/controllers/GradesController.php';
require_once __DIR__ . '/../../src/controllers/ScheduleController.php';
require_once __DIR__ . '/../../src/controllers/FormTabController.php';
require_once __DIR__ . '/../../includes/adminHandlers.php';

// ========================== DATABASE CONNECTION ==========================
if (!isset($con)) {
    require_once __DIR__ . '/../models/Database.php';
    try {
        $con = Database::connect();
    } catch (Exception $e) {
        error_log('Database connection error: ' . $e->getMessage());
        echo "<div class='alert alert-danger'>Ha ocurrido un error. Por favor, inténtelo de nuevo más tarde.</div>";
    }
}


// ========================== SAFE DEFAULTS FOR JS INJECTION ==========================
$classOptions = $classOptions ?? '';
$teacherOptions = $teacherOptions ?? '';

?>

<!-- Inject class and teacher options into JavaScript -->
<script>
    window.classOptions = `<?php echo $classOptions; ?>`;
    window.teacherOptions = `<?php echo $teacherOptions; ?>`;
</script>

<!-- ======================== ADMIN DASHBOARD UI ======================== -->
<div class="container mt-4">
    <div class="admin-dashboard">
        <h3 class="text-center mb-4">Panel de Administración</h3>

        <!-- Nav tabs -->
        <ul class="nav nav-tabs" id="adminTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="usuarios-tab" data-toggle="tab" href="#usuarios" role="tab">Usuarios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="clases-tab" data-toggle="tab" href="#clases" role="tab">Clases</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="notas-tab" data-toggle="tab" href="#notas" role="tab">Notas</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="horarios-tab" data-toggle="tab" href="#horarios" role="tab">Horarios</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="formulario-tab" data-toggle="tab" href="#formulario" role="tab">Formulario</a>
            </li>
        </ul>

        <!-- Tab content loaded via partials -->
        <div class="tab-content mt-3" id="adminTabContent">
            <?php include __DIR__ . '/partials/tab_users.php'; ?>  <!-- these tabs have the #usuarios, #clases, etc. that are referenced in the list above -->
            <?php include __DIR__ . '/partials/tab_classes.php'; ?>
            <?php include __DIR__ . '/partials/tab_grades.php'; ?>
            <?php include __DIR__ . '/partials/tab_schedule.php'; ?>
            <?php include __DIR__ . '/partials/tab_formulario.php'; ?>
        </div>
    </div>
</div>

<!-- Section-specific JavaScript -->
<script src="/assets/js/usuarios.js"></script>
<script src="/assets/js/clases.js"></script>
<script src="/assets/js/notas.js"></script>
<script src="/assets/js/horarios.js"></script>
<script src="/assets/js/formulario.js"></script>
