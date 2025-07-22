<?php
/**
 * classHandlers.php
 *
 * Handles creation, update, and deletion of class records in the Business First English Center application.
 * Used via AJAX POST requests from the admin panel.
 *
 * PHP version 7+
 *
 * @package BusinessFirstEnglishCenter
 * @author Jonathan Ray Hendrix
 * @license MIT License
 */

// ========================== DEPENDENCIES ==========================
require_once __DIR__ . '/../models/Database.php';

// ========================== CREATE CLASS ==========================

/**
 * Creates a new class with an optional assigned teacher.
 *
 * Expects:
 * - $_POST['createClass']: flag to trigger handler
 * - $_POST['classname']: string, name of the class
 * - $_POST['profesor']: (optional) int, teacher user_id
 *
 * @return void Outputs "success", "error", or "error: classname required"
 */
if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['createClass'], $_POST['classname'])
) {
    $con = Database::connect();
    $classname = trim($_POST['classname']);
    $profesor_id = isset($_POST['profesor']) ? trim($_POST['profesor']) : '';

    if ($classname === '') {
        echo "error: classname required";
        exit;
    }

    // Find the lowest available classid (to avoid gaps)
    $result = $con->query("SELECT classid FROM clases ORDER BY classid ASC");
    $expected_id = 1;
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            if ((int)$row['classid'] != $expected_id) break;
            $expected_id++;
        }
    }

    // Insert new class
    $stmt = $con->prepare("INSERT INTO clases (classid, classname) VALUES (?, ?)");
    $stmt->bind_param("is", $expected_id, $classname);
    $success1 = $stmt->execute();

    if (!$success1) {
        echo "error";
        exit;
    }

    // If no teacher assigned, we're done
    if ($profesor_id === '' || $profesor_id === null) {
        echo "success";
        exit;
    }

    // Assign teacher to class
    $stmt2 = $con->prepare("UPDATE users SET class=? WHERE user_id=?");
    $stmt2->bind_param("ii", $expected_id, $profesor_id);
    echo $stmt2->execute() ? "success" : "error";
    exit;
}

// ========================== UPDATE CLASS ==========================

/**
 * Updates an existing class and reassigns a teacher.
 *
 * Expects:
 * - $_POST['updateClass']: flag to trigger handler
 * - $_POST['classid']: int
 * - $_POST['classname']: string
 * - $_POST['profesor']: int
 *
 * @return void Outputs "success" or "error"
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateClass'])) {
    $con = Database::connect();

    $classid = intval($_POST['classid']);
    $classname = trim($_POST['classname']);
    $profesor_id = intval($_POST['profesor']);

    // Update class name
    $stmt1 = $con->prepare("UPDATE clases SET classname=? WHERE classid=?");
    $stmt1->bind_param("si", $classname, $classid);
    $success1 = $stmt1->execute();

    // Remove this class from any previously assigned teachers
    $stmt2 = $con->prepare("UPDATE users SET class='' WHERE ulevel=2 AND class=?");
    $stmt2->bind_param("i", $classid);
    $success2 = $stmt2->execute();

    // Assign the new teacher
    $stmt3 = $con->prepare("UPDATE users SET class=? WHERE user_id=?");
    $stmt3->bind_param("ii", $classid, $profesor_id);
    $success3 = $stmt3->execute();

    echo ($success1 && $success2 && $success3) ? "success" : "error";
    exit;
}

// ========================== DELETE CLASS ==========================

/**
 * Deletes a class from the database by ID.
 *
 * Expects:
 * - $_POST['deleteClass']: flag to trigger handler
 * - $_POST['classid']: int, ID of the class to delete
 *
 * @return void Outputs "success" or "error"
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['deleteClass'])) {
    $con = Database::connect();

    $classid = intval($_POST['classid']);
    $stmt = $con->prepare("DELETE FROM clases WHERE classid = ?");
    $stmt->bind_param("i", $classid);

    echo $stmt->execute() ? "success" : "error";
    exit;
}
