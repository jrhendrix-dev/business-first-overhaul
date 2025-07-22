<?php
/**
 * Header Template
 *
 * Initializes session, sets up the HTML head, navigation bar, and login modal.
 * Use $pageTitle to set the page title dynamically.
 *
 * @package BusinessFirstEnglishCenter
 */

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <!--
        Head Section
        - Sets page title (from $pageTitle if provided)
        - Includes meta tags for charset and viewport
        - Loads Bootstrap, jQuery, Popper.js, FontAwesome, and custom assets
    -->
    <link rel="icon" type="image/png" href="/assets/pics/logoNew.png"> <!-- adds page tab icon -->

    <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Academia de Idiomas' ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap CSS & JS, jQuery, Popper.js -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/css/bootstrap.min.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.1/dist/js/bootstrap.bundle.min.js"></script>

    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">


    <!-- Custom CSS & JS -->
    <link href="/assets/css/index.css" rel="stylesheet" type="text/css">
    <script src="/assets/js/common.js"></script>

    <!-- Responsive Navbar Styles -->

</head>
<body>
<!-- =========================
     DEMO NOTICE
     ========================= -->
<div id="demo-notice" class="demo-notice">
    <span>Este sitio es un proyecto ficticio con fines demostrativos.</span>
    <button id="close-notice" aria-label="Cerrar aviso">✕</button>
</div>

<!-- =========================
     NAVIGATION BAR SECTION
     ========================= -->
<nav class="navbar navbar-expand-md navbar-dark bg-dark sticky-top" id="Navegación">
    <!-- Brand Logo -->
    <a class="navbar-brand" href="/">
        <img src="/assets/pics/logoNew.png" alt="Logo" id="Navbar_Logo"/>
    </a>

    <!-- Responsive Navbar Toggler -->
    <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarResponsive" aria-controls="navbarResponsive" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
    </button>

    <!-- Navbar Links -->
    <div class="collapse navbar-collapse" id="navbarResponsive">
        <ul class="navbar-nav ml-auto list-color">
            <!-- Home -->
            <li class="nav-item">
                <a class="nav-link" href="/">Inicio</a>
            </li>
            <!-- Courses Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="cursosDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Cursos
                    <span class="custom-caret"></span>
                </a>
                <div class="dropdown-menu Menu-Style" aria-labelledby="cursosDropdown">
                    <a class="dropdown-item" href="/ingles-corporativo">Inglés corporativo</a>
                    <a class="dropdown-item" href="/examenes">Preparación exámenes oficiales</a>
                    <a class="dropdown-item" href="/clases-espanol">Español para extranjeros</a>
                </div>
            </li>
            <!-- Contact Dropdown -->
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="contactoDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Contacto
                    <span class="custom-caret"></span>
                </a>
                <div class="dropdown-menu Menu-Style" aria-labelledby="contactoDropdown">
                    <span class="dropdown-item"><a href="tel:+34635507365">Tlf: +34 635 507 365</a></span>
                    <span class="dropdown-item"><a href="mailto:jrhendrixdev@gmail.com">jrhendrixdev@gmail.com</a></span>
                    <span class="dropdown-item"> Rota (Cádiz)</span>
                </div>
            </li>
            <!-- User Authentication Links -->
            <?php if (!empty($_SESSION["user"])): ?>
                <!-- Dashboard Link (Role-based) -->
                <li class="nav-item">
                    <a class="nav-link" href='/dashboard'>
                        <?php
                        // Display user role
                        switch ($_SESSION["lvl"]) {
                            case 1:
                                echo 'Administrador';
                                break;
                            case 2:
                                echo 'Profesor';
                                break;
                            default:
                                echo 'Estudiante';
                        }
                        ?>
                    </a>
                </li>
                <!-- Logout Link -->
                <li class="nav-item">
                    <a class="nav-link" href="/logout.php">
                        <?= htmlspecialchars($_SESSION["user"]) ?>(logout)
                    </a>
                </li>
            <?php else: ?>
                <!-- Login Modal Trigger -->
                <li class="nav-item">
                    <a class="nav-link modal-button" id="login" data-toggle="modal" data-target="#login-modal" href="#">Iniciar Sesión</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<!-- =========================
     END NAVIGATION BAR
     ========================= -->

<!-- =========================
     LOGIN MODAL SECTION
     ========================= -->
<div class="container">
    <div class="modal fade" id="login-modal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header text-center">
                    <h4 class="modal-title" id="loginModalLabel">Login</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Modal Body: Login Form -->
                <form id="login-form" name="login" role="form" autocomplete="off">
                    <div class="modal-body">
                        <label for="username">Nombre de usuario</label>
                        <input type="text" name="username" id="username" placeholder="Username..." class="form-control" required />
                        <br>
                        <label for="password">Contraseña</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="Password..." required />
                        <span id="login_error" class="text-danger"></span>
                    </div>
                    <!-- Modal Footer: Actions -->
                    <div class="modal-footer">
                        <input type="submit" name="login_button" class="btn btn-success" id="login_button" value="Entrar"/>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- =========================
     END LOGIN MODAL
     ========================= -->

