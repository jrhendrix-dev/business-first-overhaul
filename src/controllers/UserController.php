<?php
/**
 * user_handlers.php
 *
 * Handles user creation, update, and deletion logic for Business First English Center.
 * Includes auto-creation of student grade records in the `notas` table.
 *
 * PHP version 7+
 *
 * @package BusinessFirstEnglishCenter
 * @author Jonathan Ray Hendrix
 * @license MIT License
 */

require_once __DIR__ . '/../models/Database.php';

// =========================================================================
// USER CREATION
// =========================================================================

if (isset($_POST['username'], $_POST['email'], $_POST['pword'], $_POST['ulevel'], $_POST['class'])) {
    $con = Database::connect();

    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['pword'];
    $ulevel = intval($_POST['ulevel']);
    $class = $_POST['class'];

    if ($class === "") {
        $class = 0;
    }

    if ($username === '' || $email === '' || $password === '' || $ulevel < 1 || $ulevel > 3) {
        http_response_code(400); // Bad Request
        echo "No. Error: Invalid input.";
        exit;
    }

    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $result = $con->query("SELECT user_id FROM users ORDER BY user_id ASC");
    $expected_id = 1;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ((int)$row['user_id'] !== $expected_id) break;
            $expected_id++;
        }
    }

    $stmt = $con->prepare("INSERT INTO users (user_id, username, email, pword, ulevel, class) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssii", $expected_id, $username, $email, $hashed_password, $ulevel, $class);

    if ($stmt->execute()) {
        if ($ulevel === 3) {
            $user_id = $con->insert_id;
            $stmt2 = $con->prepare("INSERT INTO notas (idAlumno, idClase) VALUES (?, ?)");
            $stmt2->bind_param("ii", $user_id, $class);
            if (!$stmt2->execute()) {
                http_response_code(500); // Internal Server Error
                echo "User created, but failed to create notas: " . $stmt2->error;
                exit;
            }
        }
        echo "Yes";
    } else {
        http_response_code(500);
        echo "No. Error: " . $stmt->error;
    }

    exit;
}

// =========================================================================
// USER UPDATE
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateUser'])) {
    $con = Database::connect();

    $id = $_POST['user_id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $class = $_POST['class'];
    $ulevel = $_POST['ulevel'];

    $stmt = $con->prepare("UPDATE users SET username=?, email=?, class=?, ulevel=? WHERE user_id=?");
    $stmt->bind_param("ssssi", $username, $email, $class, $ulevel, $id);

    if ($stmt->execute()) {
        if ($ulevel == 3) {
            $checkNotas = $con->prepare("SELECT 1 FROM notas WHERE idAlumno = ?");
            $checkNotas->bind_param("i", $id);
            $checkNotas->execute();
            $checkNotas->store_result();

            if ($checkNotas->num_rows === 0) {
                $insertNotas = $con->prepare("INSERT INTO notas (idAlumno) VALUES (?)");
                $insertNotas->bind_param("i", $id);
                $insertNotas->execute();
            }
            $checkNotas->close();
        }
        echo "success";
    } else {
        http_response_code(500);
        echo "error";
    }

    exit;
}

// =========================================================================
// USER DELETION
// =========================================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteUser'])) {
    $con = Database::connect();

    $id = $_POST['user_id'];
    $stmt = $con->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        echo "success";
    } else {
        http_response_code(500);
        echo "error";
    }

    exit;
}
