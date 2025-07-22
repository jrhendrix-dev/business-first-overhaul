<?php

$pageTitle = "You are not logged in";
include_once __DIR__ . '/../views/header.php';

// Mostrar mensaje si viene con ?expired=1
$expired = isset($_GET['expired']) && $_GET['expired'] == '1';
?>

<body>

<div class="login-container">
    <h2>Iniciar sesión</h2>

    <?php if ($expired): ?>
        <div class="alert">Zona protegida. Por favor, inicia sesión de nuevo.</div>
    <?php endif; ?>

    <form method="POST" id="login-screen-form" role="form" autocomplete="off">
        <div class="form-group mb-3">
            <label for="login-screen-user" class="login-screen-label">Usuario</label>
            <input type="text" name="username" id="login-screen-user" class="form-control" placeholder="username..." required>
        </div>
        <div class="form-group mb-3">
            <label for="login-screen-password">Contraseña</label>
            <input type="password" name="password" id="login-screen-password" class="form-control" placeholder="password..." required>
            <span id="login-screen-error" class="text-danger"></span>
        </div>
        <input type="submit" name="login_button" class="btn btn-success" id="login-screen-button" value="Entrar"/>

    </form>
</div>

<?php include_once __DIR__ . '/../views/footer.php'; ?>
