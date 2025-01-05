<?php
session_start();
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'paciente') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

include '../db.php'; // Conexión a la base de datos

$idUsuario = $_SESSION['idUsuario']; // ID del usuario logueado

try {
    // Obtener el idPaciente relacionado con el usuario logueado
    $queryPaciente = "SELECT idPaciente FROM paciente WHERE idUsuario = ?";
    $stmtPaciente = $conn->prepare($queryPaciente);
    $stmtPaciente->bind_param("i", $idUsuario);
    $stmtPaciente->execute();
    $resultPaciente = $stmtPaciente->get_result()->fetch_assoc();

    if (!$resultPaciente) {
        echo "<div class='container mt-5'><div class='alert alert-danger'>Error: No se encontró un paciente asociado con este usuario.</div></div>";
        exit();
    }

    $idPaciente = $resultPaciente['idPaciente'];

    // Obtener las citas médicas del paciente
    $query = "
        SELECT c.idCita, c.fecha, c.hora, c.motivo, c.estado, 
               u_pro.nombre AS especialista
        FROM citamedica c
        INNER JOIN profesionaldesalud ps ON c.idProfesional = ps.idProfesional
        INNER JOIN usuario u_pro ON ps.idUsuario = u_pro.idUsuario
        WHERE c.idPaciente = ?
        ORDER BY c.fecha ASC, c.hora ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $idPaciente);
    $stmt->execute();
    $result = $stmt->get_result();

    // Manejo de la cancelación de citas
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelarCita'])) {
        $idCita = $_POST['cancelarCita'];

        $queryCancelar = "UPDATE citamedica SET estado = 'Cancelada' WHERE idCita = ? AND idPaciente = ?";
        $stmtCancelar = $conn->prepare($queryCancelar);
        $stmtCancelar->bind_param("ii", $idCita, $idPaciente);

        if ($stmtCancelar->execute()) {
            echo "<div class='container mt-5'><div class='alert alert-success'>La cita ha sido cancelada exitosamente.</div></div>";
        } else {
            echo "<div class='container mt-5'><div class='alert alert-danger'>Error al cancelar la cita.</div></div>";
        }
        $stmtCancelar->close();

        // Refrescar la página para mostrar los cambios
        header("Location: gestionarCitasPaciente.php");
        exit();
    }

} catch (Exception $e) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Ocurrió un error: " . htmlspecialchars($e->getMessage()) . "</div></div>";
    exit();
} finally {
    $stmtPaciente->close();
    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Citas</title>
    <link rel="icon" href="../diseño/logo.png" type="image/jpg" sizes="16x16">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top bg-success">
    <div class="container">
        <a class="navbar-brand text-white" href="#">MedPlay - Mis Citas</a>
        <a href="pacienteDashboard.php" class="btn btn-danger">Regresar</a>
    </div>
</nav>

<!-- Contenido principal -->
<div class="container mt-5 pt-4">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Mis Citas</h2>
        <form method="POST" action="gestionarCitasPaciente.php">
            <table class="table table-striped table-bordered">
                <thead class="table-success">
                    <tr>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Especialista</th>
                        <th>Motivo</th>
                        <th>Estatus</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date("d/m/Y", strtotime($row['fecha']))); ?></td>
                                <td><?php echo htmlspecialchars($row['hora']); ?></td>
                                <td><?php echo htmlspecialchars($row['especialista']); ?></td>
                                <td><?php echo htmlspecialchars($row['motivo']); ?></td>
                                <td><?php echo htmlspecialchars($row['estado']); ?></td>
                                <td>
                                    <?php if ($row['estado'] === 'Programada' || $row['estado'] === 'Próxima'): ?>
                                        <button type="submit" name="cancelarCita" value="<?php echo $row['idCita']; ?>" class="btn btn-danger btn-sm">Cancelar</button>
                                    <?php else: ?>
                                        <span class="text-muted">No disponible</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center">No tienes citas registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>
</div>

<!-- Footer -->
<footer class="text-center py-3">
    <div class="container">
        <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
