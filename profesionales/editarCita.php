<?php
session_start();
include '../db.php';

// Verificar si el usuario tiene permisos
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

// Obtener los datos de la cita
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $idCita = $_GET['id'];

    $query = "SELECT * FROM citamedica WHERE idCita = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $idCita);
    $stmt->execute();
    $result = $stmt->get_result();
    $cita = $result->fetch_assoc();

    if (!$cita) {
        $_SESSION['error'] = "Cita no encontrada.";
        header("Location: gestionarCitas.php");
        exit();
    }
} else {
    $_SESSION['error'] = "ID de cita no válido.";
    header("Location: gestionarCitas.php");
    exit();
}

// Procesar el formulario de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fecha = $_POST['fecha'];
    $hora = $_POST['hora'];
    $motivo = $_POST['motivo'];

    $updateQuery = "UPDATE citamedica SET fecha = ?, hora = ?, motivo = ? WHERE idCita = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("sssi", $fecha, $hora, $motivo, $idCita);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Cita actualizada correctamente.";
        header("Location: gestionarCitas.php");
        exit();
    } else {
        $_SESSION['error'] = "Error al actualizar la cita: " . $stmt->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Cita</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Editar Cita Médica</h2>
        <form method="POST" action="">
            <div class="mb-3">
                <label for="fecha" class="form-label">Fecha:</label>
                <input type="date" id="fecha" name="fecha" class="form-control" value="<?php echo $cita['fecha']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="hora" class="form-label">Hora:</label>
                <input type="time" id="hora" name="hora" class="form-control" value="<?php echo $cita['hora']; ?>" required>
            </div>
            <div class="mb-3">
                <label for="motivo" class="form-label">Motivo:</label>
                <textarea id="motivo" name="motivo" class="form-control" required><?php echo htmlspecialchars($cita['motivo']); ?></textarea>
            </div>
            <button type="submit" class="btn btn-success w-100">Guardar Cambios</button>
            <a href="gestionarCitas.php" class="btn btn-secondary w-100 mt-2">Cancelar</a>
        </form>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
