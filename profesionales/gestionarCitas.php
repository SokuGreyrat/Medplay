<?php
session_start();
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

include '../db.php'; // Archivo de conexión a la base de datos

// Obtener el idUsuario del profesional que inició sesión
$idUsuario = $_SESSION['idUsuario'];

// Modificar la consulta para filtrar las citas asignadas al profesional actual
$query = "
    SELECT c.idCita, u_pac.nombre AS paciente, c.fecha, c.hora, 
           u_pro.nombre AS especialista, c.motivo, c.estado
    FROM citamedica c
    INNER JOIN paciente p ON c.idPaciente = p.idPaciente
    INNER JOIN usuario u_pac ON p.idUsuario = u_pac.idUsuario
    INNER JOIN profesionaldesalud ps ON c.idProfesional = ps.idProfesional
    INNER JOIN usuario u_pro ON ps.idUsuario = u_pro.idUsuario
    WHERE ps.idUsuario = ? -- Filtrar por el idUsuario del profesional actual
    ORDER BY c.fecha ASC, c.hora ASC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $idUsuario);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Citas Médicas</title>
    <link rel="icon" href="../diseño/logo.png" type="image/jpg" sizes="16x16">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/index.css" rel="stylesheet">
    <link href="../css/footer.css" rel="stylesheet">
    <link href="../css/navbar.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top bg-success">
    <div class="container">
        <a class="navbar-brand me-auto" href="proDashboard.php"><img src="../diseño/logo.png" alt="Logo 1" class="navbar-logo"></a>
        <a href="proDashboard.php" class="btn btn-danger">Regresar</a>
    </div>
</nav>

<!-- Contenido principal -->
<div class="container mt-5 pt-4">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Gestión de Citas Médicas</h2>

        <!-- Campo de búsqueda -->
        <div class="mb-3">
            <label for="buscarPaciente" class="form-label">Buscar Paciente:</label>
            <input type="text" id="buscarPaciente" class="form-control" placeholder="Escriba el nombre del paciente">
        </div>

        <!-- Tabla de citas -->
        <table class="table table-striped table-bordered">
            <thead class="table-success">
                <tr>
                    <th>Paciente</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Especialista</th>
                    <th>Motivo</th>
                    <th>Estatus</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tablaCitas">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['paciente']); ?></td>
                            <td><?php echo date("d/m/Y", strtotime($row['fecha'])); ?></td>
                            <td><?php echo htmlspecialchars($row['hora']); ?></td>
                            <td><?php echo htmlspecialchars($row['especialista']); ?></td>
                            <td><?php echo htmlspecialchars($row['motivo']); ?></td>
                            <td><?php echo htmlspecialchars($row['estado']); ?></td>
                            <td>
                                <!-- Botones de Acción -->
                                <a href="editarCita.php?id=<?php echo $row['idCita']; ?>" class="btn btn-primary btn-sm">Editar</a>
                                <a href="eliminarCita.php?id=<?php echo $row['idCita']; ?>" class="btn btn-danger btn-sm" 
                                   onclick="return confirm('¿Seguro que deseas eliminar esta cita?');">Eliminar</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center">No hay citas registradas.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Footer -->
<footer class="text-center py-3">
    <div class="container">
        <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
    </div>
</footer>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Búsqueda dinámica
    document.getElementById('buscarPaciente').addEventListener('input', function () {
        const filtro = this.value.toLowerCase();
        const filas = document.querySelectorAll('#tablaCitas tr');

        filas.forEach(fila => {
            const nombrePaciente = fila.cells[0]?.textContent.toLowerCase();
            if (nombrePaciente && nombrePaciente.includes(filtro)) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        });
    });
</script>
</body>
</html>
<?php $conn->close(); ?>
