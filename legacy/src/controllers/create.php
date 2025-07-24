<?php
/**
 * submit_form.php
 *
 * Handles contact form submissions from the public-facing site.
 * Saves the form data to the `formulario` table and redirects to a thank you page.
 *
 * PHP version 7+
 *
 * @package BusinessFirstEnglishCenter
 * @author Jonathan Ray Hendrix
 * @license MIT License
 */

// ========================== DEPENDENCIES ==========================
require_once __DIR__ . '/../../bootstrap.php';
require_once __DIR__ . '/../models/Database.php';

// ========================== VALIDATE REQUEST METHOD ==========================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo "Método no permitido.";
    exit;
}

// ========================== DB CONNECTION ==========================
$con = Database::connect();

// ========================== SET TIMEZONE ==========================
// Ensures timestamp is based on Spain time, not server default
date_default_timezone_set('Europe/Madrid');
$fecha = date('Y-m-d H:i:s');

// ========================== COLLECT FORM DATA ==========================
$nombre    = trim($_POST['nombre'] ?? '');
$apellidos = trim($_POST['apellidos'] ?? '');
$telefono  = trim($_POST['teléfono'] ?? '');
$email     = trim($_POST['email'] ?? '');
$mensaje   = trim($_POST['mensaje'] ?? '');

// ========================== VALIDATE REQUIRED FIELDS ==========================
if ($nombre && $apellidos && $email && $mensaje) {
    // Prepare INSERT statement
    $stmt = $con->prepare("
        INSERT INTO formulario (nombre, apellidos, teléfono, email, mensaje, submitted_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("ssssss", $nombre, $apellidos, $telefono, $email, $mensaje, $fecha);

    if ($stmt->execute()) {
        // Redirect to thank you page on success
        header("Location: /thanks");
        exit;
    } else {
        error_log("MySQL Error: " . $stmt->error);
        http_response_code(500);
        echo "Error al insertar: " . $stmt->error;
        exit;
    }
} else {
    http_response_code(400); // Bad Request
    echo "Por favor, complete todos los campos obligatorios.";
    exit;
}
