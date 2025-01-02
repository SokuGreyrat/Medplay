<?php
session_start();
if (!isset($_SESSION['idUsuario']) || $_SESSION['rol'] != 'profesional') {
    header("Location: ../inicio/inicioSesion.php");
    exit();
}

include '../db.php'; // Conexión a la base de datos

// Obtener lista de pacientes
$query = "SELECT p.idPaciente, u.nombre 
          FROM paciente p 
          INNER JOIN usuario u ON p.idUsuario = u.idUsuario 
          WHERE u.rol = 'paciente'";
$result_pacientes = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignar Citas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg fixed-top bg-success">
    <div class="container">
        <a class="navbar-brand text-white" href="proDashboard.php">MedPlay - Asignar Citas</a>
        <a href="proDashboard.php" class="btn btn-danger">Regresar</a>
    </div>
</nav>

<div class="container" style="margin-top: 100px;">
    <div class="card shadow p-4">
        <h2 class="text-center mb-4">Registrar Cita</h2>
        <form action="procesarCita.php" method="POST">
            <div class="mb-3">
                <label for="idPaciente" class="form-label fw-bold">Paciente:</label>
                <select id="idPaciente" name="idPaciente" class="form-select" required>
                    <option value="" disabled selected>Seleccionar paciente...</option>
                    <?php
                    if ($result_pacientes && $result_pacientes->num_rows > 0) {
                        while ($row = $result_pacientes->fetch_assoc()) {
                            echo "<option value='{$row['idPaciente']}'>{$row['nombre']}</option>";
                        }
                    } else {
                        echo "<option value='' disabled>No hay pacientes disponibles</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="fecha" class="form-label fw-bold">Fecha:</label>
                    <input type="date" id="fecha" name="fecha" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label for="hora" class="form-label fw-bold">Hora:</label>
                    <input type="time" id="hora" name="hora" class="form-control" required>
                </div>
            </div>
            <div class="mb-3">
                <label for="motivo" class="form-label fw-bold">Motivo:</label>
                <textarea id="motivo" name="motivo" class="form-control" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-success w-100">Registrar Cita</button>
        </form>
    </div>
</div>

<footer class="text-center bg-dark text-white py-3">
    <div class="container">
        <p class="mb-0">&copy; 2024 MEDPLAY. Todos los derechos reservados.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
