<?php
session_start();
include '../db.php'; // Conexión a la base de datos

// Habilitar el registro de errores para depuración
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Leer datos del cuerpo de la solicitud
$data = json_decode(file_get_contents('php://input'), true);

// Validar datos
if (!isset($data['idPaciente'], $data['tipo'], $data['descripcion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit();
}

$idPaciente = intval($data['idPaciente']);
$tipo = $data['tipo'];
$descripcion = $data['descripcion'];

// Recuperar el idUsuario asociado al idPaciente
$query_usuario = "SELECT idUsuario FROM paciente WHERE idPaciente = ?";
$stmt_usuario = $conn->prepare($query_usuario);
if (!$stmt_usuario) {
    echo json_encode(['success' => false, 'message' => 'Error al preparar consulta de usuario: ' . $conn->error]);
    exit();
}

$stmt_usuario->bind_param("i", $idPaciente);
$stmt_usuario->execute();
$result_usuario = $stmt_usuario->get_result();

if ($result_usuario->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'El paciente no está asociado a un usuario válido.']);
    exit();
}

$idUsuario = $result_usuario->fetch_assoc()['idUsuario'];

// Crear una nueva misión para el paciente
$query_insert_mision = "
    INSERT INTO usuarios_misiones (idUsuario, idMision, estadoMision, fechaAsignacion)
    VALUES (?, ?, 'Pendiente', NOW())
";

// Consulta para encontrar una misión asociada con el tratamiento
$query_mision = "
    SELECT idMision 
    FROM mision 
    WHERE nombre = ? AND descripcion = ? 
    LIMIT 1
";
$stmt_mision = $conn->prepare($query_mision);
if (!$stmt_mision) {
    echo json_encode(['success' => false, 'message' => 'Error al preparar consulta: ' . $conn->error]);
    exit();
}

$stmt_mision->bind_param("ss", $tipo, $descripcion);
$stmt_mision->execute();
$result_mision = $stmt_mision->get_result();

if ($result_mision->num_rows > 0) {
    $idMision = $result_mision->fetch_assoc()['idMision'];
} else {
    // Si la misión no existe, crear una nueva misión
    $query_create_mision = "
        INSERT INTO mision (nombre, descripcion, puntosRecompensa) 
        VALUES (?, ?, 100)";
    $stmt_create_mision = $conn->prepare($query_create_mision);
    if (!$stmt_create_mision) {
        echo json_encode(['success' => false, 'message' => 'Error al preparar creación de misión: ' . $conn->error]);
        exit();
    }
    $stmt_create_mision->bind_param("ss", $tipo, $descripcion);
    if (!$stmt_create_mision->execute()) {
        echo json_encode(['success' => false, 'message' => 'Error al crear misión: ' . $stmt_create_mision->error]);
        exit();
    }
    $idMision = $stmt_create_mision->insert_id;
}

// Insertar la asignación en usuarios_misiones
$stmt_insert = $conn->prepare($query_insert_mision);
if (!$stmt_insert) {
    echo json_encode(['success' => false, 'message' => 'Error al preparar inserción: ' . $conn->error]);
    exit();
}
$stmt_insert->bind_param("ii", $idUsuario, $idMision);

if ($stmt_insert->execute()) {
    echo json_encode(['success' => true, 'message' => 'Misión asignada exitosamente.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al asignar misión: ' . $stmt_insert->error]);
}

?>
