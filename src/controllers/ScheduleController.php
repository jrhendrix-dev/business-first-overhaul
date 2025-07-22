<?php

require_once __DIR__ . '/../models/Database.php';


/**
 * Update class schedule for a specific day.
 *
 * Handles POST requests to update the schedule for a given day, setting the class IDs for each period.
 *
 * Expects the following POST parameters:
 * - updateHorario: (any value, used as a flag)
 * - day_id: int, the ID of the day to update
 * - firstclass: string, the class ID for the first period
 * - secondclass: string, the class ID for the second period
 * - thirdclass: string, the class ID for the third period
 *
 * @return void Outputs "success" on success, "error" on failure.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateHorario'])) {
    $con = Database::connect();

    $id = $_POST['day_id'];
    $first = $_POST['firstclass'];
    $second = $_POST['secondclass'];
    $third = $_POST['thirdclass'];

    $stmt = $con->prepare("UPDATE schedule SET firstclass=?, secondclass=?, thirdclass=? WHERE day_id=?");
    $stmt->bind_param("sssi", $first, $second, $third, $id);

    echo $stmt->execute() ? "success" : "error: " . $stmt->error;
    exit;
}