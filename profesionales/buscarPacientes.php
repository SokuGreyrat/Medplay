<?php
session_start();
include '../db.php';

if (!isset($_GET['q']) || !isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    echo json_encode([]);
    exit();
}

$q = "%" . trim($_GET['q']) . "%";
$query = "
    SELECT p.idPaciente, u.nombre 
    FROM paciente p
    INNER JOIN usuario u ON p.idUsuario = u.idUsuario
    WHERE u.rol = 'paciente' AND u.nombre LIKE ?
    LIMIT 10
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $q);
$stmt->execute();
$result = $stmt->get_result();

$pacientes = [];
while ($row = $result->fetch_assoc()) {
    $pacientes[] = $row;
}

echo json_encode($pacientes);
?>
