<?php
session_start();
include '../db.php';

// Verificar permisos
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

$idPaciente = $_GET['idPaciente'] ?? null;

// Obtener lista de pacientes
$queryPacientes = "
    SELECT p.idPaciente, u.nombre 
    FROM paciente p 
    INNER JOIN usuario u ON p.idUsuario = u.idUsuario";
$resultPacientes = $conn->query($queryPacientes);

// Consulta detallada si se selecciona un paciente
$resumenPaciente = null;
$tratamientos = $vacunas = $historial = $citas = [];
$totalPuntos = 0;
$resultRecompensas = null;

if ($idPaciente) {
    // Información general del paciente, incluyendo `idUsuario`
    $queryResumen = "
        SELECT u.idUsuario, u.nombre, p.edad, p.tipoSangre, p.peso, p.altura, 
               p.telefonoEmergencia, p.fechaNacimiento, p.sexo
        FROM paciente p 
        INNER JOIN usuario u ON p.idUsuario = u.idUsuario 
        WHERE p.idPaciente = ?";
    $stmt = $conn->prepare($queryResumen);
    if (!$stmt) {
        die("Error en la consulta: " . $conn->error);
    }
    $stmt->bind_param("i", $idPaciente);
    $stmt->execute();
    $resumenPaciente = $stmt->get_result()->fetch_assoc();

    // Obtener antecedentes clínicos
    $queryAntecedentes = "SELECT * FROM antecedentesclinicos WHERE idPaciente = ?";
    $stmt = $conn->prepare($queryAntecedentes);
    if (!$stmt) {
        die("Error en la consulta de antecedentes: " . $conn->error);
    }
    $stmt->bind_param("i", $idPaciente);
    $stmt->execute();
    $antecedentes = $stmt->get_result();

    // Obtener historial médico
    $queryHistorial = "
        SELECT hm.fechaConsulta, hm.diagnostico, ps.especialidad 
        FROM historialmedico hm
        INNER JOIN profesionaldesalud ps ON hm.idProfesional = ps.idProfesional
        WHERE hm.idPaciente = ?";
    $stmt = $conn->prepare($queryHistorial);
    if (!$stmt) {
        die("Error en la consulta de historial médico: " . $conn->error);
    }
    $stmt->bind_param("i", $idPaciente);
    $stmt->execute();
    $historial = $stmt->get_result();

    // Obtener tratamientos
    $queryTratamientos = "SELECT consulta, fecha, motivo, diagnostico, medicamento, dosis, frecuencia, duracion 
    FROM tratamiento 
    WHERE idPaciente = ?";
    $stmt = $conn->prepare($queryTratamientos);
    if (!$stmt) {
        die("Error en la consulta de tratamientos: " . $conn->error);
    }
    $stmt->bind_param("i", $idPaciente);
    $stmt->execute();
    $tratamientos = $stmt->get_result();

    // Obtener vacunas
    $queryVacunas = "SELECT nombre FROM vacuna WHERE idPaciente = ?";
    $stmt = $conn->prepare($queryVacunas);
    if (!$stmt) {
        die("Error en la consulta de vacunas: " . $conn->error);
    }
    $stmt->bind_param("i", $idPaciente);
    $stmt->execute();
    $vacunas = $stmt->get_result();

    // Obtener citas médicas
    $queryCitas = "
        SELECT fecha, hora, motivo, estado 
        FROM citamedica 
        WHERE idPaciente = ?";
    $stmt = $conn->prepare($queryCitas);
    if (!$stmt) {
        die("Error en la consulta de citas médicas: " . $conn->error);
    }
    $stmt->bind_param("i", $idPaciente);
    $stmt->execute();
    $citas = $stmt->get_result();

    // Si se encontró el paciente, continuar con las consultas
    if ($resumenPaciente) {
        $idUsuario = $resumenPaciente['idUsuario'];

        // Obtener los puntos totales del paciente
        $queryPuntos = "SELECT total FROM puntos WHERE idUsuario = ? ORDER BY fechaRegistro DESC LIMIT 1";
        $stmtPuntos = $conn->prepare($queryPuntos);
        $stmtPuntos->bind_param("i", $idUsuario);
        $stmtPuntos->execute();
        $resultPuntos = $stmtPuntos->get_result()->fetch_assoc();
        $totalPuntos = $resultPuntos['total'] ?? 0;

        // Obtener las recompensas del paciente
        $queryRecompensas = "SELECT descripcion, puntosRequeridos FROM recompensa WHERE idUsuario = ?";
        $stmtRecompensas = $conn->prepare($queryRecompensas);
        $stmtRecompensas->bind_param("i", $idUsuario);
        $stmtRecompensas->execute();
        $resultRecompensas = $stmtRecompensas->get_result();
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resumen del Historial Médico</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top bg-success">
    <div class="container">
        <a class="navbar-brand text-white" href="#">MedPlay - Historial Médico</a>
        <a href="proDashboard.php" class="btn btn-danger">Regresar</a>
    </div>
</nav>

<div class="container mt-5 pt-4">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Resumen del Historial Médico</h2>

        <!-- Selección de Paciente -->
        <form method="GET" action="gestionarHistorial.php" class="mb-4">
            <div class="row">
                <div class="col-md-8">
                    <label for="idPaciente" class="form-label">Seleccione un Paciente:</label>
                    <select name="idPaciente" id="idPaciente" class="form-select" required>
                        <option value="" disabled selected>Seleccione...</option>
                        <?php while ($paciente = $resultPacientes->fetch_assoc()): ?>
                            <option value="<?php echo $paciente['idPaciente']; ?>" 
                                <?php echo ($idPaciente == $paciente['idPaciente']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($paciente['nombre']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Ver Resumen</button>
                </div>
            </div>
        </form>

        <?php if ($resumenPaciente): ?>
            <!-- Información General -->
            <h4>Información General</h4>
            <table class="table table-bordered">
                <tr>
                    <th>Nombre</th>
                    <td><?php echo htmlspecialchars($resumenPaciente['nombre']); ?></td>
                </tr>
                <tr>
                    <th>Edad</th>
                    <td><?php echo $resumenPaciente['edad']; ?> años</td>
                </tr>
                <tr>
                    <th>Tipo de Sangre</th>
                    <td><?php echo htmlspecialchars($resumenPaciente['tipoSangre']); ?></td>
                </tr>
                <tr>
                    <th>Peso</th>
                    <td><?php echo $resumenPaciente['peso']; ?> kg</td>
                </tr>
                <tr>
                    <th>Altura</th>
                    <td><?php echo $resumenPaciente['altura']; ?> m</td>
                </tr>
                <tr>
                    <th>Teléfono de Emergencia</th>
                    <td><?php echo htmlspecialchars($resumenPaciente['telefonoEmergencia']); ?></td>
                </tr>
                <tr>
                    <th>Fecha de Nacimiento</th>
                    <td><?php echo $resumenPaciente['fechaNacimiento']; ?></td>
                </tr>
                <tr>
                    <th>Sexo</th>
                    <td><?php echo htmlspecialchars($resumenPaciente['sexo']); ?></td>
                </tr>
            </table>

            <!-- Vacunas -->
            <h4>Vacunas</h4>
            <div style="max-height: 300px; overflow-y: auto;">
                <ul class="list-group">
                    <?php while ($row = $vacunas->fetch_assoc()): ?>
                        <li class="list-group-item"><?php echo $row['nombre']; ?></li>
                    <?php endwhile; ?>
                </ul>
            </div>



            <!-- Citas Médicas --><!-- Citas Médicas -->
            <h4>Citas Médicas</h4>
            <?php if ($citas->num_rows > 0): ?>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Hora</th>
                                <th>Motivo</th>
                                <th>Estado</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $fechaActual = date('Y-m-d'); // Fecha actual
                            while ($row = $citas->fetch_assoc()): 
                                $estatus = '';

                                // Lógica para determinar el estatus
                                if ($row['estado'] == 'Completada') {
                                    $estatus = '<span class="badge bg-success">Completada</span>';
                                } elseif ($row['fecha'] < $fechaActual) {
                                    $estatus = '<span class="badge bg-danger">Vencida</span>';
                                } elseif ($row['fecha'] == $fechaActual) {
                                    $estatus = '<span class="badge bg-warning text-dark">Hoy</span>';
                                } else {
                                    $estatus = '<span class="badge bg-primary">Próxima</span>';
                                }
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['fecha']); ?></td>
                                    <td><?php echo htmlspecialchars($row['hora']); ?></td>
                                    <td><?php echo htmlspecialchars($row['motivo']); ?></td>
                                    <td><?php echo htmlspecialchars($row['estado']); ?></td>
                                    <td><?php echo $estatus; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No se encontraron citas médicas registradas.</p>
            <?php endif; ?>



            <!-- Tratamientos -->
            <!-- Tratamientos -->
            <h4>Consultas y Tratamientos</h4>
            <?php if ($tratamientos->num_rows > 0): ?>
                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Consulta</th>
                                <th>Fecha</th>
                                <th>Motivo</th>
                                <th>Diagnóstico</th>
                                <th>Medicamento</th>
                                <th>Dosis</th>
                                <th>Frecuencia</th>
                                <th>Duración (días)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $tratamientos->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['consulta']); ?></td>
                                    <td><?php echo htmlspecialchars($row['fecha']); ?></td>
                                    <td><?php echo htmlspecialchars($row['motivo']); ?></td>
                                    <td><?php echo htmlspecialchars($row['diagnostico']); ?></td>
                                    <td><?php echo htmlspecialchars($row['medicamento']); ?></td>
                                    <td><?php echo htmlspecialchars($row['dosis']); ?></td>
                                    <td><?php echo htmlspecialchars($row['frecuencia']); ?></td>
                                    <td><?php echo $row['duracion']; ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p>No se encontraron tratamientos registrados.</p>
            <?php endif; ?>





             <!-- Antecedentes Clínicos -->
             <h4>Antecedentes Clínicos</h4>
            <?php if ($antecedentes->num_rows > 0): ?>
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Tipo</th>
                            <th>Descripción</th>
                            <th>Fecha Evento</th>
                            <th>Hospital Tratante</th>
                            <th>Doctor Tratante</th>
                            <th>Tratamiento</th>
                            <th>Observaciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $antecedentes->fetch_assoc()): ?>
                            <tr>
                            <td><?php echo htmlspecialchars($row['tipoAntecedente']); ?></td>
                                <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                <td><?php echo $row['fechaEvento']; ?></td>
                                <td><?php echo htmlspecialchars($row['hospitalTratante']); ?></td>
                                <td><?php echo htmlspecialchars($row['doctorTratante']); ?></td>
                                <td><?php echo htmlspecialchars($row['tratamiento']); ?></td>
                                <td><?php echo htmlspecialchars($row['observaciones']); ?></td>
                                <td>
                                    <a href="editarAntecedente.php?id=<?php echo $row['idAntecedente']; ?>" class="btn btn-sm btn-warning">Editar</a>
                                    <a href="eliminarAntecedente.php?id=<?php echo $row['idAntecedente']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Seguro que desea eliminar este antecedente?');">Eliminar</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p>No se encontraron antecedentes clínicos.</p>
            <?php endif; ?>


            <!-- Puntos y Recompensas -->
            <h4>Puntos y Recompensas</h4>
            <div class="row">
                <!-- Resumen de Puntos -->
                <div class="col-md-4 mb-3">
                    <div class="card shadow">
                        <div class="card-header bg-primary text-white">Resumen de Puntos</div>
                        <div class="card-body text-center">
                            <?php 
                            // Consulta para obtener los puntos totales del paciente
                            $queryPuntos = "SELECT total FROM puntos WHERE idUsuario = ? ORDER BY fechaRegistro DESC LIMIT 1";
                            $stmtPuntos = $conn->prepare($queryPuntos);
                            $stmtPuntos->bind_param("i", $resumenPaciente['idUsuario']);
                            $stmtPuntos->execute();
                            $resultPuntos = $stmtPuntos->get_result()->fetch_assoc();
                            $puntosTotales = $resultPuntos['total'] ?? 0;
                            ?>
                            <h3><?php echo $puntosTotales; ?> Puntos</h3>
                            <p>Acumulados hasta la fecha.</p>
                        </div>
                    </div>
                </div>

                <!-- Recompensas -->
                <div class="col-md-8 mb-3">
                    <div class="card shadow">
                        <div class="card-header bg-warning text-dark">Recompensas</div>
                        <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Descripción</th>
                                        <th>Puntos Requeridos</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Consulta para obtener las recompensas del paciente
                                    $queryRecompensas = "SELECT descripcion, puntosRequeridos FROM recompensa WHERE idUsuario = ?";
                                    $stmtRecompensas = $conn->prepare($queryRecompensas);
                                    $stmtRecompensas->bind_param("i", $resumenPaciente['idUsuario']);
                                    $stmtRecompensas->execute();
                                    $resultRecompensas = $stmtRecompensas->get_result();

                                    if ($resultRecompensas->num_rows > 0):
                                        while ($recompensa = $resultRecompensas->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($recompensa['descripcion']); ?></td>
                                                <td><?php echo htmlspecialchars($recompensa['puntosRequeridos']); ?></td>
                                            </tr>
                                        <?php endwhile;
                                    else: ?>
                                        <tr>
                                            <td colspan="2" class="text-center">No se encontraron recompensas disponibles.</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>



              <!-- Historial Médico -->
            <h4>Historial Médico</h4>
            <table class="table table-bordered">
                <thead><tr><th>Fecha</th><th>Diagnóstico</th><th>Especialidad</th></tr></thead>
                <tbody>
                    <?php while ($row = $historial->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['fechaConsulta']; ?></td>
                            <td><?php echo $row['diagnostico']; ?></td>
                            <td><?php echo $row['especialidad']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>

           





            <!-- Botón Generar Historial -->
            <div class="text-center mt-4">
                <form action="generarHistorial.php" method="POST" target="_blank">
                    <input type="hidden" name="idPaciente" value="<?php echo $idPaciente; ?>">
                    <button type="submit" class="btn btn-success btn-lg">Generar Historial</button>
                </form>
            </div>
        <?php endif; ?>
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php $conn->close(); ?>
