<?php
/**
 * tab_users.php
 *
 * Tab content for managing users within the admin dashboard.
 * Includes user creation form and user listing container.
 *
 * @package BusinessFirstEnglishCenter
 */



// ========================== SAFEGUARDS FOR TEMPLATE VARIABLES ==========================
$classOptions = $classOptions ?? '';
?>

<script>
    window.classOptions = `<?php echo $classOptions; ?>`;
</script>

<div class="tab-pane fade show active" id="usuarios" role="tabpanel">
    <div class="admin-section mb-4 px-3 px-md-4">
        <div class="user-toggle-wrapper">
            <button id="toggleUserForm" class="btn-toggle-user mb-3">
                + Añadir usuario
            </button>
            <div id="userFormContainer" class="user-form-collapsible">
                <form id="user-create-form">
                    <div class="form-group mb-2">
                        <label for="username">Nombre de usuario</label>
                        <input type="text" id="username" name="username" placeholder="Nombre de usuario" class="form-control" required />
                    </div>
                    <div class="form-group mb-2">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="Email" class="form-control" required />
                    </div>
                    <div class="form-group mb-2">
                        <label for="pword">Contraseña</label>
                        <input type="password" id="pword" name="pword" placeholder="Contraseña" class="form-control" required />
                    </div>
                    <div class="form-group mb-2">
                        <label for="ulevel">Rango de usuario</label>
                        <select name="ulevel" class="form-control" id="ulevel" required>
                            <option value="" disabled selected>Rango de usuario</option>
                            <option value="1">Admin</option>
                            <option value="2">Profesor</option>
                            <option value="3">Alumno</option>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="class">Clase asignada</label>
                        <select name="class" class="form-control" id="class">
                            <option value="" disabled selected>Seleccione una clase</option>
                            <option value="">Sin clase</option>
                            <?= $classOptions ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">Crear usuario</button>
                </form>
            </div>
        </div>
        <div id="create-user-feedback" class="mt-2 text-success"></div>
    </div>
    <div class="admin-section px-3 px-md-4">
        <h4>Lista de usuarios</h4>
        <div id="user-table-container"></div>
    </div>
</div>
