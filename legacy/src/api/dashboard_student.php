<?php
/**
 * dashboard_student.php
 *
 * Renders the main dashboard view for students.
 * Displays two tabs: one for grades and one for class schedules.
 * Also injects relevant class data into JavaScript for AJAX-based loading.
 *
 * PHP version 7+
 *
 * @package BusinessFirstEnglishCenter
 * @author Jonathan Ray Hendrix <jrhendrixdev@gmail.com>
 * @license MIT License
 */

// ========================== DEPENDENCIES & INITIALIZATION ==========================

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../includes/studentHandlers.php';

// Ensure critical variables are defined before injecting into the frontend
$studentClassId = $studentClassId ?? '';
$className = $className ?? '';

?>

<!-- ========================== JAVASCRIPT VARIABLES INJECTION ========================== -->
<script>
    /**
     * Injected class ID and name for use in AJAX scripts.
     * Used in student_dash.js for context-aware data loading.
     */
    window.studentClassId = `<?= $studentClassId ?>`;
    window.class_name = `<?= $className ?>`;
</script>

<!-- ========================== STUDENT DASHBOARD VIEW ========================== -->
<div class="container mt-4">
    <div class="student-dashboard">
        <h3 class="text-center mb-4">Panel de Estudiante</h3>

        <!-- ========================== NAVIGATION TABS ========================== -->
        <ul class="nav nav-tabs" id="studentTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="student-tab" data-toggle="tab" href="#students" role="tab" aria-controls="students" aria-selected="true">
                    Notas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="schedule-tab" data-toggle="tab" href="#schedule" role="tab" aria-controls="schedule" aria-selected="false">
                    Horario de la Clase
                </a>
            </li>
        </ul>

        <!-- ========================== TAB CONTENT ========================== -->
        <div class="tab-content mt-3" id="studentTabsContent">

            <!-- ========== TAB: GRADES ========== -->
            <div class="tab-pane fade show active" id="students" role="tabpanel" aria-labelledby="student-tab">
                <h5 class="mb-3">Clase: <?= htmlspecialchars($className) ?></h5>
                <div id="student-grades-table-container">
                    <!-- AJAX-loaded content via student_dash.js -->
                </div>
            </div>

            <!-- ========== TAB: SCHEDULE ========== -->
            <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                <h5 class="mb-3">Horario de la Clase: <?= htmlspecialchars($className) ?></h5>
                <div id="student-schedule-table-container">
                    <!-- AJAX-loaded content via student_dash.js -->
                </div>
            </div>

        </div> <!-- /tab-content -->
    </div>
</div>

<!-- ========================== JAVASCRIPT MODULE FOR STUDENT DASHBOARD ========================== -->
<script src="/assets/js/student_dash.js"></script>
