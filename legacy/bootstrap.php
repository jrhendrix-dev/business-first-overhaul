<?php

/**
 * bootstrap.php
 *
 * Global initialization file for the Business First English Center application.
 *
 * Responsibilities:
 * - Configures secure session cookie parameters (HttpOnly, Secure).
 * - Starts the PHP session if not already started.
 * - Establishes a connection to the MySQL database and assigns it to $con.
 * - Loads authentication helper functions.
 *
 * Usage:
 * - This file should be included at the very top of every PHP page or entry point.
 * - Ensures consistent session and database setup across the application.
 *
 * @package    BusinessFirstEnglishCenter
 * @author     Jonathan Ray Hendrix <jrhendrixdev@gmail.com>
 * @license    MIT License
 */

/*
 These ini_set calls configure how PHP will create the session cookie.
They must be set before the session is started, so that the cookie is created with the correct flags.
If you set them after session_start(), the session cookie may already have been sent to the browser without those flags.
 */

// Seguridad para la sesión

ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Cambiar a 1 si usas HTTPS

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base de datos
require_once __DIR__ . '/src/models/Database.php';
require_once __DIR__ . '/includes/auth.php'; //  This line includes i
$con = Database::connect();

// Funciones de autenticación
if (!function_exists('check_login')) {
    function check_login() {
        return isset($_SESSION['login']);
    }
}


