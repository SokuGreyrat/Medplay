<?php
session_start();
include '../db.php'; // Conexión a la base de datos

// Validar que se reciba el parámetro idPaciente
if (!isset($_GET['idPaciente'])) {
    echo json_encode([]);
    exit();
}

$idPaciente = intval($_GET['idPaciente']);

// Obtener el idUsuario asociado al idPaciente
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
    echo json_encode([]);
    exit();
}

$idUsuario = $result_usuario->fetch_assoc()['idUsuario'];

// Consultar tratamientos y verificar si ya están asignados como misión
$query_tratamientos = "
    SELECT 
        'Tratamiento' AS tipo, 
        CONCAT('Medicamento: ', t.medicamento, ', Dosis: ', t.dosis, ', Frecuencia: ', t.frecuencia) AS descripcion,
        EXISTS (
            SELECT 1
            FROM usuarios_misiones um
            INNER JOIN mision m ON um.idMision = m.idMision
            WHERE um.idUsuario = ? AND m.nombre = 'Tratamiento' 
              AND m.descripcion = CONCAT('Medicamento: ', t.medicamento, ', Dosis: ', t.dosis, ', Frecuencia: ', t.frecuencia)
        ) AS asignada
    FROM tratamiento t
    WHERE t.idPaciente = ?";
$stmt_tratamientos = $conn->prepare($query_tratamientos);
$stmt_tratamientos->bind_param("ii", $idUsuario, $idPaciente);
$stmt_tratamientos->execute();
$result_tratamientos = $stmt_tratamientos->get_result();
$tratamientos = $result_tratamientos->fetch_all(MYSQLI_ASSOC);

// Consultar misiones directamente asignadas al usuario
$query_misiones = "
    SELECT 
        'Misión' AS tipo, 
        CONCAT('Misión: ', m.nombre, ', Descripción: ', m.descripcion) AS descripcion,
        1 AS asignada
    FROM usuarios_misiones um
    INNER JOIN mision m ON um.idMision = m.idMision
    WHERE um.idUsuario = ?";
$stmt_misiones = $conn->prepare($query_misiones);
$stmt_misiones->bind_param("i", $idUsuario);
$stmt_misiones->execute();
$result_misiones = $stmt_misiones->get_result();
$misiones = $result_misiones->fetch_all(MYSQLI_ASSOC);

// Unificar resultados
$asignaciones = array_merge($tratamientos, $misiones);

// Devolver los resultados como JSON
echo json_encode($asignaciones);
?>
