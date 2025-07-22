<?php
require_once __DIR__ . '/../models/Database.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['idformulario'])) {

    $con = Database::connect();

    $idformulario = $_POST['idformulario'];
    $stmt = $con->prepare("DELETE FROM formulario WHERE idformulario = ?");
    $stmt->bind_param("i", $idformulario);

    echo $stmt->execute() ? "success" : "error";
    exit;
}