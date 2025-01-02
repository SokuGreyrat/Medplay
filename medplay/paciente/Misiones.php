<?php
session_start();
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'paciente') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

include '../db.php'; // Conexión a la base de datos

$idUsuario = $_SESSION['idUsuario'];
$nombre = $_SESSION['nombre'];

// Obtener datos de peso y altura del paciente
$queryPaciente = "SELECT peso, altura FROM paciente WHERE idUsuario = ?";
$stmtPaciente = $conn->prepare($queryPaciente);
$stmtPaciente->bind_param("i", $idUsuario);
$stmtPaciente->execute();
$resultPaciente = $stmtPaciente->get_result()->fetch_assoc();

$peso = $resultPaciente['peso'] ?? 0;
$altura = $resultPaciente['altura'] ?? 0;

// Calcular el IMC (Índice de Masa Corporal)
$imc = ($altura > 0) ? $peso / (($altura / 100) ** 2) : 0;
$estadoIMC = "";
$tipoMisiones = [];

if ($imc > 0) {
    if ($imc < 18.5) {
        $estadoIMC = "Peso bajo. Es importante consultar a un médico o nutricionista.";
        $tipoMisiones = ["Nutrición", "Ejercicio ligero", "Descanso adecuado"];
    } elseif ($imc >= 18.5 && $imc < 24.9) {
        $estadoIMC = "Peso normal. ¡Sigue cuidándote!";
        $tipoMisiones = ["Mantenimiento físico", "Cuidado emocional", "Actividad social"];
    } elseif ($imc >= 25 && $imc < 29.9) {
        $estadoIMC = "Sobrepeso. Considera una alimentación balanceada y actividad física.";
        $tipoMisiones = ["Ejercicio moderado", "Nutrición saludable", "Gestión del estrés"];
    } else {
        $estadoIMC = "Obesidad. Se recomienda buscar orientación médica y nutricional.";
        $tipoMisiones = ["Actividad física supervisada", "Plan alimenticio", "Apoyo emocional"];
    }
}

// Verificar si $tipoMisiones tiene elementos
if (!empty($tipoMisiones)) {
    $tipos = "'" . implode("','", $tipoMisiones) . "'";
    $queryMisiones = "
        SELECT m.idMision, m.nombre, m.descripcion, m.puntosRecompensa, um.estadoMision, um.fechaAsignacion, um.fechaCompletada
        FROM usuarios_misiones um
        INNER JOIN mision m ON um.idMision = m.idMision
        WHERE um.idUsuario = ? AND m.tipo IN ($tipos)
        ORDER BY um.estadoMision ASC, um.fechaAsignacion DESC";
} else {
    $queryMisiones = "
        SELECT m.idMision, m.nombre, m.descripcion, m.puntosRecompensa, um.estadoMision, um.fechaAsignacion, um.fechaCompletada
        FROM usuarios_misiones um
        INNER JOIN mision m ON um.idMision = m.idMision
        WHERE um.idUsuario = ?
        ORDER BY um.estadoMision ASC, um.fechaAsignacion DESC";
}

$stmtMisiones = $conn->prepare($queryMisiones);

// Verificar errores en la preparación de la consulta
if (!$stmtMisiones) {
    die("Error en la preparación de la consulta: " . $conn->error);
}

$stmtMisiones->bind_param("i", $idUsuario);
$stmtMisiones->execute();
$resultMisiones = $stmtMisiones->get_result();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Misiones del Paciente</title>
    <link rel="icon" href="../diseño/logo.png" type="image/jpg" sizes="16x16">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-success">
    <div class="container">
        <a class="navbar-brand" href="#">MedPlay - Misiones</a>
        <div class="d-flex align-items-center">
            <a href="../inicio/inicioSesion.php" class="btn btn-danger">Cerrar Sesión</a>
        </div>
    </div>
</nav>

<!-- Contenido principal -->
<div class="container mt-5">
    <h2 class="text-center mb-4">Misiones Asignadas</h2>

    <!-- Sección de Cuidado Personal -->
    <div class="card mb-4">
        <div class="card-header bg-info text-white">Cuidado Personal</div>
        <div class="card-body">
            <p><strong>Tu peso:</strong> <?php echo number_format($peso, 2); ?> kg</p>
            <p><strong>Tu altura:</strong> <?php echo number_format($altura, 2); ?> cm</p>
            <p><strong>Tu IMC:</strong> <?php echo number_format($imc, 2); ?></p>
            <p><strong>Estado:</strong> <?php echo $estadoIMC; ?></p>
            <p><strong>Recomendaciones:</strong> Basado en tu estado, te sugerimos completar misiones relacionadas con: <?php echo implode(", ", $tipoMisiones); ?>.</p>
        </div>
    </div>

    <!-- Lista de misiones -->
    <div class="row">
        <?php while ($mision = $resultMisiones->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <?php echo htmlspecialchars($mision['nombre']); ?>
                    </div>
                    <div class="card-body">
                        <p><strong>Descripción:</strong> <?php echo htmlspecialchars($mision['descripcion']); ?></p>
                        <p><strong>Puntos Recompensa:</strong> <?php echo $mision['puntosRecompensa']; ?></p>
                        <p><strong>Estado:</strong> <?php echo $mision['estadoMision']; ?></p>
                        <p><strong>Asignada el:</strong> <?php echo $mision['fechaAsignacion']; ?></p>
                        <?php if ($mision['estadoMision'] == 'Completada'): ?>
                            <p><strong>Completada el:</strong> <?php echo $mision['fechaCompletada']; ?></p>
                        <?php else: ?>
                            <a href="registrarAvanceMision.php?idMision=<?php echo $mision['idMision']; ?>" class="btn btn-success">Registrar Avance</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>

        <?php if ($resultMisiones->num_rows == 0): ?>
            <div class="col-12 text-center">
                <p class="text-muted">No tienes misiones asignadas actualmente.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Footer -->
<footer class="mt-auto text-center text-white bg-dark py-3">
    <div class="container">
        <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
    </div>
</footer>

</body>
</html>

<?php
$conn->close();
?>
