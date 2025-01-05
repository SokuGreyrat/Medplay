<?php
session_start();
include '../db.php'; // Conexión a la base de datos

if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'paciente') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$idUsuario = $_SESSION['idUsuario'];

// Consultar las próximas citas del paciente
$queryCitas = "
    SELECT c.fecha, c.hora, c.motivo, p.especialidad, p.hospitalAsociado 
    FROM citamedica c
    INNER JOIN profesionaldesalud p ON c.idProfesional = p.idProfesional
    WHERE c.idPaciente IN (SELECT idPaciente FROM paciente WHERE idUsuario = ?) 
      AND (c.estado = 'Programada' OR c.estado = 'Próxima')
    ORDER BY c.fecha ASC, c.hora ASC
    LIMIT 5";
$stmt = $conn->prepare($queryCitas);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();

$citas = [];
while ($row = $result->fetch_assoc()) {
    $citas[] = [
        'fecha' => $row['fecha'],
        'hora' => $row['hora'],
        'motivo' => $row['motivo'],
        'especialidad' => $row['especialidad'],
        'hospitalAsociado' => $row['hospitalAsociado']
    ];
}

echo json_encode([
    'success' => true,
    'citas' => $citas
]);

$conn->close();
?>
