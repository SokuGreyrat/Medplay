<?php
session_start();
include '../db.php'; // Conexión a la base de datos

// Validar que se reciba el parámetro idPaciente
if (!isset($_GET['idPaciente'])) {
    echo json_encode([]);
    exit();
}

$idPaciente = intval($_GET['idPaciente']);

// Consulta para obtener las citas del paciente, incluyendo el estado
$query = "SELECT idCita, fecha, hora, motivo, estado 
          FROM citamedica 
          WHERE idPaciente = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $idPaciente);
$stmt->execute();
$result = $stmt->get_result();

// Verificar si hay resultados
$citas = [];
while ($row = $result->fetch_assoc()) {
    // Verificar si la cita está vencida comparando la fecha actual
    $fechaActual = date('Y-m-d');
    if ($row['estado'] === 'Próxima' && $row['fecha'] < $fechaActual) {
        $row['estado'] = 'Vencida'; // Marcar como vencida si ya pasó la fecha
    }
    $citas[] = $row;
}

// Devolver los resultados como JSON
echo json_encode($citas);
?>
