<?php
/**
 * save_grades.php
 *
 * Handles creation or update of student grades in the `notas` table.
 * If the student does not have a row in 'notas', one is created.
 * Validates that grades are either NULL or between 0 and 10.
 *
 * PHP version 7+
 *
 * @package BusinessFirstEnglishCenter
 * @author Jonathan Ray Hendrix
 * @license MIT License
 */

require_once __DIR__ . '/../models/Database.php';

/**
 * Save or update student grades based on POST parameters.
 *
 * Expected POST parameters:
 * - updateNota: (flag to trigger this logic)
 * - idAlumno: int - the student ID
 * - nota1: float|null
 * - nota2: float|null
 * - nota3: float|null
 *
 * @return void Echoes 'success' or error messages.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['updateNota'])) {
    $con = Database::connect();

    $id = $_POST['idAlumno'];

    // Extract and normalize grade inputs
    $nota1 = isset($_POST['nota1']) && $_POST['nota1'] !== "" ? floatval($_POST['nota1']) : null;
    $nota2 = isset($_POST['nota2']) && $_POST['nota2'] !== "" ? floatval($_POST['nota2']) : null;
    $nota3 = isset($_POST['nota3']) && $_POST['nota3'] !== "" ? floatval($_POST['nota3']) : null;

    /**
     * Validates a single grade value (0–10 or NULL).
     *
     * @param float|null $n
     * @return bool
     */
    function validarNota($n) {
        return is_null($n) || (is_numeric($n) && $n >= 0 && $n <= 10);
    }

    // Validate grade ranges
    if (!validarNota($nota1) || !validarNota($nota2) || !validarNota($nota3)) {
        echo "error: valores de nota fuera de rango";
        exit;
    }

    // Check if the student already has a grade row
    $res = $con->query("SELECT * FROM notas WHERE idAlumno = $id");

    // Prepare query dynamically based on whether we insert or update
    if ($res && $res->num_rows > 0) {
        // ========== UPDATE ==========
        $sql = "UPDATE notas SET 
            Nota1=" . (is_null($nota1) ? "NULL" : "?") . ", 
            Nota2=" . (is_null($nota2) ? "NULL" : "?") . ", 
            Nota3=" . (is_null($nota3) ? "NULL" : "?") . " 
            WHERE idAlumno=?";

        $stmt = $con->prepare($sql);
    } else {
        // ========== INSERT ==========
        $sql = "INSERT INTO notas (Nota1, Nota2, Nota3, idAlumno) VALUES (" .
            (is_null($nota1) ? "NULL" : "?") . ", " .
            (is_null($nota2) ? "NULL" : "?") . ", " .
            (is_null($nota3) ? "NULL" : "?") . ", ?)";

        $stmt = $con->prepare($sql);
    }

    // Dynamic bind_param construction
    $bindTypes = "";
    $bindValues = [];

    if (!is_null($nota1)) { $bindTypes .= "d"; $bindValues[] = $nota1; }
    if (!is_null($nota2)) { $bindTypes .= "d"; $bindValues[] = $nota2; }
    if (!is_null($nota3)) { $bindTypes .= "d"; $bindValues[] = $nota3; }

    $bindTypes .= "i";
    $bindValues[] = $id;

    $stmt->bind_param($bindTypes, ...$bindValues);

    echo $stmt->execute() ? "success" : "error: " . $stmt->error;
    exit;
}
