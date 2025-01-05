<?php
session_start();
include '../db.php';

// Verificar permisos
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'paciente') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

// Obtener el ID del paciente basado en la sesión del usuario
$idUsuario = $_SESSION['idUsuario'];

// Consultar información del paciente
$queryPaciente = "
    SELECT p.idPaciente, u.nombre, p.edad, p.tipoSangre, p.peso, p.altura, 
           p.telefonoEmergencia, p.fechaNacimiento, p.sexo
    FROM paciente p 
    INNER JOIN usuario u ON p.idUsuario = u.idUsuario 
    WHERE u.idUsuario = ?";
$stmtPaciente = $conn->prepare($queryPaciente);
$stmtPaciente->bind_param("i", $idUsuario);
$stmtPaciente->execute();
$resumenPaciente = $stmtPaciente->get_result()->fetch_assoc();

if (!$resumenPaciente) {
    die('No se encontró información del paciente.');
}

$idPaciente = $resumenPaciente['idPaciente'];

// Consultar datos adicionales del historial
$queries = [
    'vacunas' => "SELECT nombre FROM vacuna WHERE idPaciente = ?",
    'citas' => "SELECT fecha, hora, motivo, estado FROM citamedica WHERE idPaciente = ?",
    'tratamientos' => "SELECT consulta, fecha, motivo, diagnostico, medicamento, dosis, frecuencia, duracion FROM tratamiento WHERE idPaciente = ?",
    'antecedentes' => "SELECT tipoAntecedente, descripcion, fechaEvento, hospitalTratante, doctorTratante, tratamiento, observaciones FROM antecedentesclinicos WHERE idPaciente = ?"
];

$data = [];
foreach ($queries as $key => $query) {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $idPaciente);
    $stmt->execute();
    $data[$key] = $stmt->get_result();
}

// Consultar puntos totales
$queryTotalPuntos = "SELECT total FROM puntos WHERE idUsuario = ? ORDER BY fechaRegistro DESC, idPuntos DESC LIMIT 1";
$stmtPuntos = $conn->prepare($queryTotalPuntos);
$stmtPuntos->bind_param("i", $idUsuario);
$stmtPuntos->execute();
$resultPuntos = $stmtPuntos->get_result();
$totalPuntos = $resultPuntos->fetch_assoc()['total'] ?? 0;

// Consultar recompensas
$queryRecompensas = "SELECT descripcion, puntosRequeridos FROM recompensa WHERE idUsuario = ?";
$stmtRecompensas = $conn->prepare($queryRecompensas);
$stmtRecompensas->bind_param("i", $idUsuario);
$stmtRecompensas->execute();
$resultRecompensas = $stmtRecompensas->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historial Médico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top bg-success">
    <div class="container">
        <a class="navbar-brand text-white" href="#">MedPlay - Historial Médico</a>
        <a href="pacienteDashboard.php" class="btn btn-danger">Regresar</a>
    </div>
</nav>

<div class="container mt-5 pt-4">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Historial Médico</h2>

        <!-- Información General -->
        <h4>Información General</h4>
        <table class="table table-bordered">
            <tr><th>Nombre</th><td><?php echo htmlspecialchars($resumenPaciente['nombre']); ?></td></tr>
            <tr><th>Edad</th><td><?php echo $resumenPaciente['edad']; ?> años</td></tr>
            <tr><th>Tipo de Sangre</th><td><?php echo htmlspecialchars($resumenPaciente['tipoSangre']); ?></td></tr>
            <tr><th>Peso</th><td><?php echo $resumenPaciente['peso']; ?> kg</td></tr>
            <tr><th>Altura</th><td><?php echo $resumenPaciente['altura']; ?> m</td></tr>
            <tr><th>Teléfono de Emergencia</th><td><?php echo htmlspecialchars($resumenPaciente['telefonoEmergencia']); ?></td></tr>
            <tr><th>Fecha de Nacimiento</th><td><?php echo $resumenPaciente['fechaNacimiento']; ?></td></tr>
            <tr><th>Sexo</th><td><?php echo htmlspecialchars($resumenPaciente['sexo']); ?></td></tr>
        </table>

        <!-- Vacunas -->
        <h4>Vacunas</h4>
        <div style="max-height: 300px; overflow-y: auto;">
            <ul class="list-group">
                <?php while ($row = $data['vacunas']->fetch_assoc()): ?>
                    <li class="list-group-item"><?php echo $row['nombre']; ?></li>
                <?php endwhile; ?>
            </ul>
        </div>

        <!-- Citas Médicas -->
        <h4>Citas Médicas</h4>
        <?php if ($data['citas']->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead><tr><th>Fecha</th><th>Hora</th><th>Motivo</th><th>Estado</th></tr></thead>
                <tbody>
                    <?php while ($row = $data['citas']->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['fecha']); ?></td>
                            <td><?php echo htmlspecialchars($row['hora']); ?></td>
                            <td><?php echo htmlspecialchars($row['motivo']); ?></td>
                            <td><?php echo htmlspecialchars($row['estado']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No se encontraron citas médicas registradas.</p>
        <?php endif; ?>

        <!-- Tratamientos -->
        <h4>Consultas y Tratamientos</h4>
        <?php if ($data['tratamientos']->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead><tr><th>Consulta</th><th>Fecha</th><th>Motivo</th><th>Diagnóstico</th><th>Medicamento</th></tr></thead>
                <tbody>
                    <?php while ($row = $data['tratamientos']->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['consulta']); ?></td>
                            <td><?php echo htmlspecialchars($row['fecha']); ?></td>
                            <td><?php echo htmlspecialchars($row['motivo']); ?></td>
                            <td><?php echo htmlspecialchars($row['diagnostico']); ?></td>
                            <td><?php echo htmlspecialchars($row['medicamento']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No se encontraron tratamientos registrados.</p>
        <?php endif; ?>

        <!-- Antecedentes Clínicos -->
        <h4>Antecedentes Clínicos</h4>
        <?php if ($data['antecedentes']->num_rows > 0): ?>
            <table class="table table-bordered">
                <thead><tr><th>Tipo</th><th>Descripción</th><th>Fecha</th></tr></thead>
                <tbody>
                    <?php while ($row = $data['antecedentes']->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['tipoAntecedente']); ?></td>
                            <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                            <td><?php echo htmlspecialchars($row['fechaEvento']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No se encontraron antecedentes clínicos.</p>
        <?php endif; ?>

        <!-- Puntos y Recompensas -->
        <div class="row">
            <div class="col-md-4">
                <h4>Puntos</h4>
                <div class="card shadow">
                    <div class="card-body text-center">
                        <h3><?php echo $totalPuntos; ?> Puntos</h3>
                        <p>Acumulados hasta la fecha.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <h4>Recompensas</h4>
                <table class="table table-bordered">
                    <thead><tr><th>Descripción</th><th>Puntos Requeridos</th></tr></thead>
                    <tbody>
                        <?php while ($row = $resultRecompensas->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                <td><?php echo htmlspecialchars($row['puntosRequeridos']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
