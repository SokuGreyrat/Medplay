<?php
session_start();
include '../db.php';

$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['idPaciente'], $data['nombre'], $data['descripcion'], $data['puntos'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit();
}

$idPaciente = intval($data['idPaciente']);
$nombre = $data['nombre'];
$descripcion = $data['descripcion'];
$puntos = intval($data['puntos']);

// Obtener idUsuario
$query_usuario = "SELECT idUsuario FROM paciente WHERE idPaciente = ?";
$stmt_usuario = $conn->prepare($query_usuario);
$stmt_usuario->bind_param("i", $idPaciente);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();

if ($result_usuario->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Paciente no encontrado.']);
    exit();
}

$idUsuario = $result_usuario->fetch_assoc()['idUsuario'];

// Insertar misión
$query_mision = "INSERT INTO mision (nombre, descripcion, puntosRecompensa) VALUES (?, ?, ?)";
$stmt_mision = $conn->prepare($query_mision);
$stmt_mision->bind_param("ssi", $nombre, $descripcion, $puntos);

if (!$stmt_mision->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error al crear misión: ' . $stmt_mision->error]);
    exit();
}

$idMision = $stmt_mision->insert_id;

// Asignar misión al usuario
$query_asignacion = "INSERT INTO usuarios_misiones (idUsuario, idMision, estadoMision, fechaAsignacion) VALUES (?, ?, 'Pendiente', NOW())";
$stmt_asignacion = $conn->prepare($query_asignacion);
$stmt_asignacion->bind_param("ii", $idUsuario, $idMision);

if ($stmt_asignacion->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al asignar misión: ' . $stmt_asignacion->error]);
}
?>
