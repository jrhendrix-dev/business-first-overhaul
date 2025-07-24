<?php
/**
 * @file index.php
 * @description Central router and front controller for the Business First English Center project.
 * Uses clean URLs (via .htaccess) and routes requests to views, controllers, or API handlers.
 *
 * @author Jonathan Ray Hendrix <jrhendrixdev@gmail.com>
 * @license MIT
 */

// Extract the request path from the URL (ignoring query string)
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Base path of the application (adjust if hosted in a subfolder)
$basePath = '/';

// Define all route mappings
switch ($request) {
    ///////////////////////////
    // Public Pages
    ///////////////////////////

    case $basePath:
    case $basePath . 'home':
        require_once '../views/home.php';
        break;

    case $basePath . 'ingles-corporativo':
        require_once 'ic.php';
        break;

    case $basePath . 'examenes':
        require_once 'examenes.php';
        break;

    case $basePath . 'clases-espanol':
        require_once 'clasesesp.php';
        break;

    case $basePath . 'login_screen':
        require_once 'login_screen.php';
        break;

    case $basePath . 'thanks':
        require_once 'gracias.php';
        break;

    ///////////////////////////
    // Internal Controllers
    ///////////////////////////

    case $basePath . 'create':
        require_once __DIR__ . '/../src/controllers/create.php';
        break;

    case $basePath . 'dashboard':
        // Legacy or fallback dashboard view
        require_once '../views/dashboard.php';
        break;

    ///////////////////////////
    // API Routes
    ///////////////////////////

    case $basePath . 'api/admin':
        require_once __DIR__ . '/../src/api/dashboard_admin.php';
        exit;

    case $basePath . 'api/student':
        require_once __DIR__ . '/../src/api/dashboard_student.php';
        exit;

    case $basePath . 'api/teacher':
        require_once __DIR__ . '/../src/api/dashboard_teacher.php';
        exit;

    ///////////////////////////
    // 404 Not Found Fallback
    ///////////////////////////

    default:
        http_response_code(404);
        echo '404 Not Found';
        break;
}
