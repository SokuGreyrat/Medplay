<?php
session_start();
include '../db.php';

if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

// Obtener la lista de pacientes
$query = "SELECT p.idPaciente, u.nombre FROM paciente p INNER JOIN usuario u ON p.idUsuario = u.idUsuario";
$result = $conn->query($query);
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
    <form method="GET" action="detalleHistorial.php">
        <div class="mb-3">
            <label for="idPaciente" class="form-label">Seleccione un Paciente:</label>
            <select id="idPaciente" name="idPaciente" class="form-select" required>
                <option value="" disabled selected>Seleccione...</option>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <option value="<?php echo $row['idPaciente']; ?>">
                        <?php echo htmlspecialchars($row['nombre']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary w-100">Ver Historial</button>
    </form>
</div>
</body>
</html>
<?php $conn->close(); ?>
