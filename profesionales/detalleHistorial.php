<?php
session_start();
include '../db.php';

if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

$idPaciente = $_GET['idPaciente'];

// Obtener datos personales
$queryPaciente = "
    SELECT u.nombre, p.edad, p.tipoSangre 
    FROM paciente p 
    INNER JOIN usuario u ON p.idUsuario = u.idUsuario 
    WHERE p.idPaciente = ?";
$stmt = $conn->prepare($queryPaciente);
$stmt->bind_param("i", $idPaciente);
$stmt->execute();
$resultPaciente = $stmt->get_result();
$paciente = $resultPaciente->fetch_assoc();

// Obtener tratamientos
$queryTratamientos = "SELECT medicamento, dosis, frecuencia, duracion FROM tratamiento WHERE idPaciente = $idPaciente";
$tratamientos = $conn->query($queryTratamientos);

// Obtener vacunas
$queryVacunas = "SELECT nombre, fechaAplicacion FROM vacuna WHERE idPaciente = $idPaciente";
$vacunas = $conn->query($queryVacunas);

// Obtener citas
$queryCitas = "SELECT fecha, hora, motivo, estado FROM citamedica WHERE idPaciente = $idPaciente";
$citas = $conn->query($queryCitas);

// Obtener historial médico
$queryHistorial = "
    SELECT diagnostico, fechaConsulta, u.nombre AS profesional
    FROM historialmedico h 
    INNER JOIN profesionaldesalud ps ON h.idProfesional = ps.idProfesional
    INNER JOIN usuario u ON ps.idUsuario = u.idUsuario
    WHERE h.idPaciente = $idPaciente";
$historial = $conn->query($queryHistorial);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial del Paciente</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="text-center mb-4">Historial Completo del Paciente</h2>

    <!-- Datos Personales -->
    <h4>Datos Personales</h4>
    <p><strong>Nombre:</strong> <?php echo htmlspecialchars($paciente['nombre']); ?></p>
    <p><strong>Edad:</strong> <?php echo $paciente['edad']; ?></p>
    <p><strong>Tipo de Sangre:</strong> <?php echo htmlspecialchars($paciente['tipoSangre']); ?></p>

    <!-- Tratamientos -->
    <h4>Tratamientos</h4>
    <ul>
        <?php while ($row = $tratamientos->fetch_assoc()): ?>
            <li><?php echo "{$row['medicamento']} - Dosis: {$row['dosis']}, Frecuencia: {$row['frecuencia']}, Duración: {$row['duracion']} días"; ?></li>
        <?php endwhile; ?>
    </ul>

    <!-- Vacunas -->
    <h4>Vacunas</h4>
    <ul>
        <?php while ($row = $vacunas->fetch_assoc()): ?>
            <li><?php echo "{$row['nombre']} - Aplicada el: {$row['fechaAplicacion']}"; ?></li>
        <?php endwhile; ?>
    </ul>

    <!-- Citas Médicas -->
    <h4>Citas Médicas</h4>
    <ul>
        <?php while ($row = $citas->fetch_assoc()): ?>
            <li><?php echo "Fecha: {$row['fecha']}, Hora: {$row['hora']}, Motivo: {$row['motivo']}, Estado: {$row['estado']}"; ?></li>
        <?php endwhile; ?>
    </ul>

    <!-- Historial Médico -->
    <h4>Historial Médico</h4>
    <ul>
        <?php while ($row = $historial->fetch_assoc()): ?>
            <li><?php echo "Diagnóstico: {$row['diagnostico']} (Fecha: {$row['fechaConsulta']}) - Realizado por: {$row['profesional']}"; ?></li>
        <?php endwhile; ?>
    </ul>
</div>
</body>
</html>
<?php $conn->close(); ?>
