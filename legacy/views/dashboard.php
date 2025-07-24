<?php


// Enable output buffering (only once per request)
if (!headers_sent()) {
    ob_start();
}

$pageTitle = "Dashboard | Business First English Center";
require_once __DIR__ . '/../bootstrap.php';
include_once __DIR__ . '/../views/header.php';
require_once __DIR__ . '/../includes/adminHandlers.php';
require_once __DIR__ . '/../src/models/Database.php';

if (!check_login()) {
    if (!headers_sent()) {
        header("Location: login_screen?expired=1");
    } else {
        echo "Error: los headers ya fueron enviados.";
    }
    exit();
}

$con = Database::connect();
include_once __DIR__ . '/header.php';
?>

<div class="wrapper">


    <div class="content">
        <h2  id="dashboard-header">Usuario: <?php echo htmlspecialchars($_SESSION["user"]); ?>.</h2>

        <?php
        switch ($_SESSION["lvl"]) {
            case 1:
                include __DIR__ . '/../src/api/dashboard_admin.php';
                break;
            case 2:
                include __DIR__ . '/../src/api/dashboard_teacher.php';
                break;
            case 3:
                include __DIR__ . '/../src/api/dashboard_student.php';
                break;
        }
        ?>
    </div>

</div>

<?php

$con->close();
include_once __DIR__ . '/footer.php';
?>
