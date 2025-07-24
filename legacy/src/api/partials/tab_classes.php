<?php
/**
 * tab_classes.php
 *
 * Tab content for managing classes within the admin dashboard.
 * Includes class creation form and class listing container.
 *
 * @package BusinessFirstEnglishCenter
 */


// ========================== SAFEGUARDS FOR TEMPLATE VARIABLES ==========================
$teacherOptions = $teacherOptions ?? '';
?>

<script>
    window.teacherOptions = `<?php echo $teacherOptions; ?>`;
</script>

<div class="tab-pane fade" id="clases" role="tabpanel">
    <div class="admin-section mb-4 px-3 px-md-4">
        <div class="class-toggle-wrapper">
            <button id="toggleClassForm" class="btn-toggle-class mb-3">
                + Añadir clase
            </button>
            <div id="classFormContainer" class="class-form-collapsible">
                <form id="class-create-form">
                    <div class="form-group mb-2">
                        <label for="classname">Nombre del curso</label>
                        <input type="text" id="classname" name="classname" placeholder="Nombre del curso" class="form-control" required />
                    </div>
                    <div class="form-group mb-3">
                        <label for="profesor">Profesor</label>
                        <select id="profesor" name="profesor" class="form-control" required>
                            <option value="" disabled selected>Seleccione un profesor</option>
                            <?= $teacherOptions ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Crear clase</button>
                </form>
            </div>
        </div>
        <div id="create-class-feedback" class="mt-2 text-success"></div>
    </div>
    <div class="admin-section px-3 px-md-4">
        <h4>Lista de clases</h4>
        <div id="class-table-container"></div>
    </div>
</div>
