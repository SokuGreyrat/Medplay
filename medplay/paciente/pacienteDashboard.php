<?php
session_start();
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'paciente') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

include '../db.php'; // Conexión a la base de datos

$idUsuario = $_SESSION['idUsuario'];
$nombre = $_SESSION['nombre'];

// Obtener resumen de puntos
$queryPuntos = "SELECT total FROM puntos WHERE idUsuario = ?";
$stmtPuntos = $conn->prepare($queryPuntos);
$stmtPuntos->bind_param("i", $idUsuario);
$stmtPuntos->execute();
$resultPuntos = $stmtPuntos->get_result()->fetch_assoc();
$totalPuntos = $resultPuntos['total'] ?? 0;

// Obtener próximas citas asignadas por profesionales
$queryCitas = "
    SELECT c.fecha, c.hora, c.motivo, p.especialidad, p.hospitalAsociado 
    FROM citamedica c
    INNER JOIN profesionaldesalud p ON c.idProfesional = p.idProfesional
    WHERE c.idPaciente IN (SELECT idPaciente FROM paciente WHERE idUsuario = ?) 
      AND (c.estado = 'Programada' OR c.estado = 'Próxima')
    ORDER BY c.fecha ASC, c.hora ASC
    LIMIT 3";
$stmtCitas = $conn->prepare($queryCitas);
$stmtCitas->bind_param("i", $idUsuario);
$stmtCitas->execute();
$resultCitas = $stmtCitas->get_result();

// Obtener notificaciones
$queryNotificaciones = "SELECT actividad, fechaRegistro FROM puntos WHERE idUsuario = ? ORDER BY fechaRegistro DESC LIMIT 3";
$stmtNotificaciones = $conn->prepare($queryNotificaciones);
$stmtNotificaciones->bind_param("i", $idUsuario);
$stmtNotificaciones->execute();
$resultNotificaciones = $stmtNotificaciones->get_result();



// Obtener el total más reciente desde la tabla puntos
$queryPuntos = "SELECT total FROM puntos WHERE idUsuario = ? ORDER BY fechaRegistro DESC LIMIT 1";
$stmtPuntos = $conn->prepare($queryPuntos);
$stmtPuntos->bind_param("i", $idUsuario);
$stmtPuntos->execute();
$resultPuntos = $stmtPuntos->get_result()->fetch_assoc();
$totalPuntos = $resultPuntos['total'] ?? 0;


// Obtener el último múltiplo de 150 alcanzado
$ultimoMultiplo = floor($totalPuntos / 150) * 150;

// Verificar si ya existe una recompensa para este múltiplo
$queryVerificarRecompensa = "SELECT COUNT(*) as total FROM recompensa WHERE idUsuario = ? AND puntosRequeridos = ?";
$stmtVerificar = $conn->prepare($queryVerificarRecompensa);
$stmtVerificar->bind_param("ii", $idUsuario, $ultimoMultiplo);
$stmtVerificar->execute();
$resultVerificar = $stmtVerificar->get_result()->fetch_assoc();

if ($resultVerificar['total'] == 0 && $ultimoMultiplo > 0) {
    // Generar una recompensa aleatoria
    // Generar una recompensa aleatoria
    $recompensasPosibles = [
        "Consulta médica gratuita",
        "Descuento del 20% en medicamentos recetados",
        "Acceso premium a contenido educativo sobre salud durante un mes",
        "Análisis de laboratorio gratuito (glucosa o colesterol)",
        "Sesión de terapia física gratuita",
        "Consulta de seguimiento gratuita con un especialista",
        "Descuento del 30% en vacunas opcionales",
        "Check-up básico gratuito (presión arterial, peso, IMC)",
        "Entrega gratuita de medicamentos a domicilio por una semana",
        "Suscripción gratuita a boletines de salud personalizados",
        "Clase virtual gratuita sobre cuidado de la salud mental",
        "Acceso a un seminario en línea sobre nutrición y dietas saludables",
        "Guía descargable gratuita para ejercicios en casa",
        "Cuaderno de registros médicos personalizado",
        "Descuento en servicios de odontología preventiva",
        "Sesión gratuita de orientación psicológica",
        "Acceso gratuito a una clase grupal de yoga o meditación",
        "Descuento en consultas con especialistas (ejemplo: dermatología)",
        "Kit de primeros auxilios básico",
        "Plan de alimentación saludable personalizado gratuito"
    ];


    $descripcionRecompensa = $recompensasPosibles[random_int(0, count($recompensasPosibles) - 1)];
    $puntosRequeridos = $ultimoMultiplo;

    // Insertar la recompensa en la tabla `recompensa`
    $queryInsertarRecompensa = "INSERT INTO recompensa (descripcion, puntosRequeridos, idUsuario) 
                                VALUES (?, ?, ?)";
    $stmtInsertarRecompensa = $conn->prepare($queryInsertarRecompensa);
    $stmtInsertarRecompensa->bind_param("sii", $descripcionRecompensa, $puntosRequeridos, $idUsuario);
    $stmtInsertarRecompensa->execute();
}


// Obtener recompensas generadas para el usuario
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
    <title>Dashboard Paciente</title>
    <link rel="icon" href="../diseño/logo.png" type="image/jpg" sizes="16x16">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="#">MedPlay - Dashboard Paciente</a>

        <!-- Botón de Notificaciones con Despliegue -->
        <div class="d-flex align-items-center">
            <div class="dropdown">
                <button class="btn btn-outline-light d-flex align-items-center dropdown-toggle" type="button" id="dropdownNotificaciones" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell me-1"></i> Notificaciones
                </button>
                <ul class="dropdown-menu dropdown-menu-end p-3" aria-labelledby="dropdownNotificaciones" style="width: 300px; max-height: 400px; overflow-y: auto;">
                    <?php
                    // Obtener citas del paciente
                    $queryCitasNotificaciones = "
                        SELECT fecha, hora, motivo, estado 
                        FROM citamedica 
                        WHERE idPaciente IN (SELECT idPaciente FROM paciente WHERE idUsuario = ?) 
                        ORDER BY fecha ASC, hora ASC LIMIT 5";
                    $stmtCitasNotificaciones = $conn->prepare($queryCitasNotificaciones);
                    $stmtCitasNotificaciones->bind_param("i", $idUsuario);
                    $stmtCitasNotificaciones->execute();
                    $resultCitasNotificaciones = $stmtCitasNotificaciones->get_result();

                    while ($cita = $resultCitasNotificaciones->fetch_assoc()): ?>
                        <li class="mb-2">
                            <strong>Fecha:</strong> <?php echo $cita['fecha']; ?><br>
                            <strong>Estado:</strong> <?php echo htmlspecialchars($cita['estado']); ?>
                        </li>
                        <hr class="my-2">
                    <?php endwhile; ?>
                    <?php if ($resultCitasNotificaciones->num_rows == 0): ?>
                        <li class="text-center text-muted">No hay citas registradas.</li>
                    <?php endif; ?>
                </ul>
            </div>
            <a href="../inicio/inicioSesion.php" class="btn btn-danger ms-2">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<!-- Contenido principal -->
<div class="container mt-5">
    <h2 class="text-center mb-4">Bienvenido, <?php echo htmlspecialchars($nombre); ?>!</h2>

    <!-- Secciones -->
    <div class="row">
        <!-- Resumen de Puntos -->
        <div class="col-md-4 mb-3">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">Resumen de Puntos</div>
                <div class="card-body text-center">
                    <h3><?php echo $totalPuntos; ?> Puntos</h3>
                    <p>Acumulados hasta la fecha.</p>
                </div>
            </div>
        </div>

        <!-- Recompensas -->
        <div class="col-md-4 mb-3">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">Recompensas</div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <ul class="list-unstyled">
                        <?php while ($recompensa = $resultRecompensas->fetch_assoc()): ?>
                            <li>
                                <strong>Recompensa:</strong> <?php echo htmlspecialchars($recompensa['descripcion']); ?><br>
                                <strong>Puntos Requeridos:</strong> <?php echo $recompensa['puntosRequeridos']; ?>
                            </li>
                            <hr>
                        <?php endwhile; ?>
                        <?php if ($resultRecompensas->num_rows == 0): ?>
                            <p class="text-center">No hay recompensas disponibles.</p>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>



        <!-- Citas Próximas -->
        <div class="col-md-4 mb-3">
            <div class="card shadow">
                <div class="card-header bg-info text-white">Citas Próximas</div>
                <div class="card-body" style="max-height: 300px; overflow-y: auto;">
                    <ul class="list-unstyled">
                        <?php while ($cita = $resultCitas->fetch_assoc()): ?>
                            <li>
                                <strong>Fecha:</strong> <?php echo $cita['fecha']; ?><br>
                                <strong>Hora:</strong> <?php echo $cita['hora']; ?><br>
                                <strong>Motivo:</strong> <?php echo htmlspecialchars($cita['motivo']); ?><br>
                                <strong>Especialidad:</strong> <?php echo htmlspecialchars($cita['especialidad']); ?><br>
                                <strong>Hospital:</strong> <?php echo htmlspecialchars($cita['hospitalAsociado']); ?>
                            </li>
                            <hr>
                        <?php endwhile; ?>
                        <?php if ($resultCitas->num_rows == 0): ?>
                            <p class="text-center">No hay citas próximas.</p>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>



    <!-- Secciones de Acciones -->
    <div class="row text-center mt-4">
        <div class="col-md-3 mb-3">
            <a href="gestionarCitasPaciente.php" class="btn btn-primary w-100">Gestionar Citas</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="resumenVacunas.php" class="btn btn-primary w-100">Vizualizar Historial</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="Misiones.php" class="btn btn-primary w-100">Misiones</a>
        </div>
        <div class="col-md-3 mb-3">
            <a href="soporte.html" class="btn btn-primary w-100">Soporte</a>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="mt-auto text-center text-white bg-dark py-3">
    <div class="container">
        <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
    </div>
</footer>

<script>
    // Inicialización de Bootstrap Dropdown
    document.addEventListener('DOMContentLoaded', function () {
        const dropdownElement = document.getElementById('dropdownNotificaciones');
        if (dropdownElement) {
            new bootstrap.Dropdown(dropdownElement);
        }
    });
</script>
</body>
</html>

<?php
$conn->close();
?>