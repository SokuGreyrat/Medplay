<?php
session_start();
include '../db.php'; // Conexión a la base de datos

if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'paciente') {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$idUsuario = $_SESSION['idUsuario'];

// Consultar todas las notificaciones del usuario (actividades registradas en la tabla `puntos`)
$queryNotificaciones = "
    SELECT actividad, fechaRegistro 
    FROM puntos 
    WHERE idUsuario = ? 
    ORDER BY fechaRegistro DESC";
$stmt = $conn->prepare($queryNotificaciones);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();

$notificaciones = [];
while ($row = $result->fetch_assoc()) {
    $notificaciones[] = [
        'actividad' => $row['actividad'],
        'fechaRegistro' => $row['fechaRegistro']
    ];
}

echo json_encode([
    'success' => true,
    'notificaciones' => $notificaciones
]);

$conn->close();
?>
