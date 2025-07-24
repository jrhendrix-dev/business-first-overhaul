<?php
/**
 * dashboard_teacher.php
 *
 * Renders the main dashboard view for teachers.
 * Displays two tabs: one for managing students and grades,
 * and one for viewing the assigned class schedule.
 *
 * PHP version 7+
 *
 * @package BusinessFirstEnglishCenter
 * @author  Jonathan Ray Hendrix <jrhendrixdev@gmail.com>
 * @license MIT License
 */

// ========================== DEPENDENCIES & INITIALIZATION ==========================

require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../../includes/teacherHandlers.php';

// Define fallback values if not already set
$teacherClassId = $teacherClassId ?? '';
$className = $className ?? '';

?>

<!-- ========================== JAVASCRIPT VARIABLE INJECTION ========================== -->
<script>
    /**
     * Injected teacher class context into the frontend.
     * Used in teacher_dash.js to load class-specific data.
     */
    window.teacherClassId = `<?= $teacherClassId ?>`;
    window.class_name = `<?= $className ?>`;
</script>

<!-- ========================== TEACHER DASHBOARD VIEW ========================== -->
<div class="container mt-4">
    <div class="teacher-dashboard">
        <h3 class="text-center mb-4">Panel de Profesor</h3>

        <!-- ========================== NAVIGATION TABS ========================== -->
        <ul class="nav nav-tabs" id="teacherTabs" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="students-tab" data-toggle="tab" href="#students" role="tab" aria-controls="students" aria-selected="true">
                    Alumnos y Notas
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="schedule-tab" data-toggle="tab" href="#schedule" role="tab" aria-controls="schedule" aria-selected="false">
                    Horario de la Clase
                </a>
            </li>
        </ul>

        <!-- ========================== TAB CONTENT ========================== -->
        <div class="tab-content mt-3" id="teacherTabsContent">

            <!-- ========== TAB: STUDENTS & GRADES ========== -->
            <div class="tab-pane fade show active" id="students" role="tabpanel" aria-labelledby="students-tab">
                <h5 class="mb-3">Clase: <?= htmlspecialchars($className) ?></h5>
                <div id="teacher-students-table-container">
                    <!-- Loaded via AJAX using teacher_dash.js -->
                </div>
            </div>

            <!-- ========== TAB: CLASS SCHEDULE ========== -->
            <div class="tab-pane fade" id="schedule" role="tabpanel" aria-labelledby="schedule-tab">
                <h5 class="mb-3">Horario de la Clase: <?= htmlspecialchars($className) ?></h5>
                <div id="teacher-schedule-table-container">
                    <!-- Loaded via AJAX using teacher_dash.js -->
                </div>
            </div>

        </div> <!-- /tab-content -->
    </div>
</div>

<!-- ========================== JAVASCRIPT MODULE FOR TEACHER DASHBOARD ========================== -->
<script src="/assets/js/teacher_dash.js"></script>
